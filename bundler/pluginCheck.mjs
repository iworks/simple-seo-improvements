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
import {
    existsSync,
    mkdirSync,
    readFileSync,
    rmSync,
    readdirSync,
    writeFileSync,
} from "node:fs";
import { log, executionTime } from "./consoleHelpers.mjs";
import { spawnAsync } from "./spawnAsync.mjs";
import { config } from "./config.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

// Plugin version from package.json (single source of truth).
const { version } = JSON.parse(
    readFileSync(join(root, "package.json"), "utf8"),
);

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
 * @param {boolean} quiet Print a summary + assessment table instead of the
 *   full listing (used inside `bun run build`).
 * @return {Promise<{errors: number, warnings: number}>}
 */
async function runCheck(ignoreWarnings, quiet = false) {
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

    // Full report to pcp-check.log.
    const logLines = [
        "# Plugin Check (PCP) report",
        `# plugin: ${config.name} ${version}`,
        `# date:   ${new Date().toISOString()}`,
        `# errors: ${errors.length}, warnings: ${warnings.length}`,
        "",
        ...rows.map(
            (r) => `${r.type}\t${r.file}:${r.line}\t${r.code}\t${r.message}`,
        ),
        "",
    ];
    writeFileSync(join(root, "pcp-check.log"), logLines.join("\n"));

    if (quiet) {
        if (!rows.length) {
            log("Plugin Check passed — no errors, no warnings.", "s");
        } else {
            log(
                `Plugin Check: ${errors.length} error(s), ${warnings.length} warning(s) — full report in pcp-check.log`,
                errors.length ? "e" : "s",
            );
            console.log(pcpTable(rows));
        }
        return { errors: errors.length, warnings: warnings.length };
    }

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
            `\nPlugin Check: ${errors.length} error(s), ${warnings.length} warning(s) — full report in pcp-check.log`,
            errors.length ? "e" : "s",
        );
    }
    return { errors: errors.length, warnings: warnings.length };
}

/**
 * Human-friendly short label for a PCP warning code.
 *
 * @param {string} code Raw check code.
 * @return {string} Short label.
 */
function pcpCodeLabel(code) {
    const labels = {
        "WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound":
            "Dynamic hook name",
        "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound":
            "Non-prefixed hook name",
        "WordPress.NamingConventions.PrefixAllGlobals.InvalidPrefixPassed":
            "Invalid prefix (false positive)",
        "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound":
            "Core constant (by design)",
        "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound":
            "Non-prefixed global variable",
        "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound":
            "Non-prefixed function name",
        "WordPress.Security.NonceVerification.Recommended":
            "No nonce verification",
        "WordPress.Security.ValidatedSanitizedInput.MissingUnslash":
            "Input not unslashed",
        "WordPress.Security.ValidatedSanitizedInput.InputNotSanitized":
            "Input not sanitized",
        "WordPress.Security.ValidatedSanitizedInput.InputNotValidated":
            "Input not validated",
        "WordPress.Security.EscapeOutput.OutputNotEscaped":
            "Output not escaped",
        "WordPress.Security.EscapeOutput.UnsafePrintingFunction":
            "Unsafe printing function",
        "WordPress.WP.I18n.TextDomainMismatch": "Text domain mismatch",
        "WordPress.WP.I18n.MissingArgDomain": "Missing text domain",
        "WordPress.WP.I18n.NonSingularStringLiteralText":
            "Non-literal i18n string",
        "WordPress.WP.AlternativeFunctions.strip_tags_strip_tags":
            "strip_tags() discouraged",
        "WordPress.DB.DirectDatabaseQuery.DirectQuery": "Direct DB query",
        "WordPress.DB.DirectDatabaseQuery.NoCaching":
            "DB query without caching",
        "WordPress.DB.SlowDBQuery.slow_db_query_meta_query": "Slow meta_query",
        "WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude":
            "Exclusionary query param",
        "PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound":
            "Discouraged load_plugin_textdomain",
    };
    return labels[code] ?? code.split(".").pop() ?? code;
}

/**
 * Assessment for a PCP finding: upstream submodule items are deferred,
 * core-hook names are accepted (WordPress core API surface), the rest are
 * judged per-code.
 *
 * @param {object} row Parsed report row.
 * @param {string} row.file File path from the report.
 * @param {string} row.code Raw check code.
 * @return {string} Assessment.
 */
function pcpAssessment(row) {
    if (row.file.startsWith("includes/iworks/options/")) {
        return "upstream (iworks/options)";
    }
    if (row.file.startsWith("includes/iworks/rate/")) {
        return "upstream (iworks/rate)";
    }
    const byCode = {
        "WordPress.NamingConventions.PrefixAllGlobals.InvalidPrefixPassed":
            "accepted (false positive)",
        "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound":
            "accepted (core hook names)",
        "WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound":
            "accepted (core hook names)",
        "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound":
            "accepted (intentional)",
        "WordPress.WP.I18n.TextDomainMismatch": "accepted (staging slug)",
        "WordPress.Security.NonceVerification.Recommended": "review",
    };
    return byCode[row.code] ?? "review";
}

/**
 * Render an ASCII table with columns: location, warning, assessment.
 *
 * @param {Array<{file: string, line: string, code: string}>} rows Parsed rows.
 * @return {string} Rendered table.
 */
function pcpTable(rows) {
    const cells = rows.map((r) => [
        `${r.file}${r.line ? `:${r.line}` : ""}`,
        pcpCodeLabel(r.code),
        pcpAssessment(r),
    ]);
    const headers = ["location", "warning", "assessment"];
    const widths = headers.map((h, i) =>
        Math.max(h.length, ...cells.map((c) => c[i].length)),
    );
    const line = (l, m, r) =>
        l + widths.map((w) => "─".repeat(w + 2)).join(m) + r;
    const row = (c) =>
        "│ " + c.map((v, i) => v.padEnd(widths[i])).join(" │ ") + " │";
    return [
        line("┌", "┬", "┐"),
        row(headers),
        line("├", "┼", "┤"),
        ...cells.map(row),
        line("└", "┴", "┘"),
    ].join("\n");
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
 * @param {boolean} options.quiet Print summary + table instead of full
 *   listing (used inside `bun run build`).
 * @return {Promise<number>} Exit code (0 = clean).
 */
export async function pluginCheck({
    skipBuild = false,
    ignoreWarnings = false,
    optional = false,
    quiet = false,
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
        result = await runCheck(ignoreWarnings, quiet);
    });
    rmSync(stagingDir, { recursive: true, force: true });
    return result.errors ? 1 : 0;
}
