/**
 * Plugin Check (PCP) — stages the built wp.org ZIP into the WordPress
 * plugins directory and runs `wp plugin check` against it.
 *
 * The staging directory is named `{name}-pcp`, but `--slug={name}` is
 * passed so the i18n checks validate against the real textdomain instead
 * of the staging directory name (no slug-mismatch noise).
 *
 * Prerequisites: a running WordPress install the plugin dir belongs to
 * (auto-detected: <repo>/../../../wp-load.php), `wp` CLI and `unzip`,
 * and the `plugin-check` plugin installed on that site.
 *
 * Flags:
 *   --skip-build  check the existing release/wporg/{name}.zip as-is
 *   --ignore-warnings  only report errors
 */

import { join, resolve, dirname } from "node:path";
import { existsSync, mkdirSync, rmSync, readdirSync } from "node:fs";
import { log, executionTime } from "./consoleHelpers.mjs";
import { spawnAsync } from "./spawnAsync.mjs";
import { config } from "./config.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

// WordPress root: repo lives in wp-content/plugins/{dir}.
const wpRoot = process.env.WP_ROOT || join(root, "..", "..", "..");
const pluginsDir = join(wpRoot, "wp-content", "plugins");
const zipPath = join(root, config.releaseDir, "wporg", `${config.name}.zip`);
const stagingSlug = `${config.name}-pcp`;
const stagingDir = join(pluginsDir, stagingSlug);

/**
 * Extract the ZIP's inner plugin directory into the staging directory.
 *
 * @return {Promise<void>}
 */
async function stage() {
    if (!existsSync(zipPath)) {
        const err = new Error(
            `Missing ${zipPath} — run \`bun run build:wporg\` first.`,
        );
        err.friendly = true;
        throw err;
    }
    rmSync(stagingDir, { recursive: true, force: true });
    const tmp = join(root, "release", "pcp-tmp");
    rmSync(tmp, { recursive: true, force: true });
    mkdirSync(tmp, { recursive: true });
    await spawnAsync("unzip", ["-q", zipPath, "-d", tmp], "unzip");
    const inner = readdirSync(tmp).find((name) =>
        existsSync(join(tmp, name, `${config.name}.php`)),
    );
    if (!inner) {
        rmSync(tmp, { recursive: true, force: true });
        const err = new Error(
            `Could not find the plugin directory inside the ZIP.`,
        );
        err.friendly = true;
        throw err;
    }
    mkdirSync(pluginsDir, { recursive: true });
    await spawnAsync("cp", ["-r", join(tmp, inner), stagingDir], "stage copy");
    rmSync(tmp, { recursive: true, force: true });
}

/**
 * Parse one CSV line (comma-separated, double-quoted fields).
 *
 * @param {string} line Raw line.
 * @return {string[]} Fields.
 */
function splitCsvLine(line) {
    const fields = [];
    let field = "";
    let quoted = false;
    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        if (quoted) {
            if (char === '"' && line[i + 1] === '"') {
                field += '"';
                i++;
            } else if (char === '"') {
                quoted = false;
            } else {
                field += char;
            }
        } else if (char === '"') {
            quoted = true;
        } else if (char === ",") {
            fields.push(field);
            field = "";
        } else {
            field += char;
        }
    }
    fields.push(field);
    return fields;
}

/**
 * Parse plugin-check CSV output into rows.
 *
 * @param {string} csv Raw CSV.
 * @return {Array<{file: string, line: string, type: string, code: string, message: string}>}
 */
function parseCsv(csv) {
    // plugin-check prints "FILE: <path>" header lines between per-file rows.
    const rows = [];
    let file = "";
    for (const rawLine of csv.split("\n")) {
        if (rawLine.startsWith("FILE: ")) {
            file = rawLine.slice(6).trim();
            continue;
        }
        const fields = splitCsvLine(rawLine);
        if (fields.length < 5 || fields[0] === "line") {
            continue;
        }
        rows.push({
            file,
            line: fields[0].trim(),
            type: fields[2].trim(),
            code: fields[3].trim(),
            message: fields[4]?.trim() ?? "",
        });
    }
    return rows;
}

/**
 * Run the check on the staged copy and clean up.
 *
 * @param {boolean} ignoreWarnings Skip warnings from the report.
 * @return {Promise<{errors: number, warnings: number}>}
 */
async function runCheck(ignoreWarnings) {
    const args = [
        "plugin",
        "check",
        stagingSlug,
        `--path=${wpRoot}`,
        `--slug=${config.name}`,
        "--format=csv",
    ];
    if (ignoreWarnings) {
        args.push("--ignore-warnings");
    }
    const result = await spawnAsync("wp", args, "wp plugin check", false);
    const rows = parseCsv(result.stdout);
    const errors = rows.filter((r) => r.type === "ERROR");
    const warnings = rows.filter((r) => r.type === "WARNING");
    for (const kind of [
        ["ERROR", errors],
        ["WARNING", warnings],
    ]) {
        if (!kind[1].length) {
            continue;
        }
        log(`\n${kind[0]}S (${kind[1].length}):`, "w");
        for (const row of kind[1]) {
            log(
                `  ${row.file}:${row.line} ${row.code} — ${row.message}`,
                kind[0] === "ERROR" ? "e" : "w",
            );
        }
    }
    if (!rows.length) {
        log("Plugin Check passed — no errors, no warnings.", "s");
    } else {
        log(
            `\nPlugin Check: ${errors.length} error(s), ${warnings.length} warning(s)`,
            errors.length ? "e" : "s",
        );
    }
    return { errors: errors.length, warnings: warnings.length };
}

/**
 * Check the PCP prerequisites without failing: `wp` CLI must exist and the
 * plugin-check plugin must be available on the detected site.
 *
 * @return {Promise<boolean>} True when the check can run.
 */
async function checkPrerequisites() {
    const wp = await spawnAsync("wp", ["--info"], "wp --info", false);
    if (wp.code !== 0) {
        return false;
    }
    const check = await spawnAsync(
        "wp",
        ["plugin", "list", `--path=${wpRoot}`, "--format=json", "--no-color"],
        "wp plugin list",
        false,
    );
    if (check.code !== 0) {
        return false;
    }
    try {
        const plugins = JSON.parse(check.stdout || "[]");
        const pluginCheck = plugins.find(
            (p) => p.name === "plugin-check" && p.status === "active",
        );
        return Boolean(pluginCheck);
    } catch {
        return false;
    }
}

/**
 * Full check flow.
 *
 * @param {object}  options
 * @param {boolean} options.skipBuild Use the existing ZIP.
 * @param {boolean} options.ignoreWarnings Only fail on errors.
 * @param {boolean} options.optional Skip silently when the environment has
 *   no `wp` CLI or the plugin-check plugin (used inside `bun run build`).
 * @return {Promise<number>} Exit code (0 = clean).
 */
export async function pluginCheck({
    skipBuild = false,
    ignoreWarnings = false,
    optional = false,
} = {}) {
    if (optional && !(await checkPrerequisites())) {
        log(
            "pcp — skipped (needs `wp` CLI + the plugin-check plugin on the site)",
            "w",
        );
        return 0;
    }
    if (!skipBuild) {
        await spawnAsync(
            "bun",
            ["bundler/index.js", "build:wporg"],
            "build:wporg",
        );
    }
    let result = { errors: 0, warnings: 0 };
    await executionTime("pcp", async () => {
        await stage();
        result = await runCheck(ignoreWarnings);
    });
    rmSync(stagingDir, { recursive: true, force: true });
    return result.errors ? 1 : 0;
}
