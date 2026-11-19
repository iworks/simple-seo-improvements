/**
 * Translations — wp-cli i18n make-pot / update-po / make-mo.
 * Replaces grunt-wp-i18n (makepot) and grunt-potomo.
 */

import { dirname, join, resolve } from "node:path";
import {
    copyFileSync,
    existsSync,
    readFileSync,
    readdirSync,
    renameSync,
    unlinkSync,
} from "node:fs";
import { log } from "./consoleHelpers.mjs";
import { config } from "./config.mjs";
import { spawnAsync } from "./spawnAsync.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");
const WP_CLI_URL =
    "https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar";

/**
 * Make sure wp-cli.phar is available; download it when missing.
 */
async function ensureWpCli() {
    const phar = join(root, config.wpCli);
    if (existsSync(phar)) {
        return phar;
    }
    log("wp-cli.phar not found — downloading…", "w");
    const result = await spawnAsync(
        "curl",
        ["-sSfL", WP_CLI_URL, "-o", config.wpCli],
        "wp-cli download",
        false,
    );
    if (result.code !== 0 || !existsSync(phar)) {
        const err = new Error(
            "wp-cli.phar is missing and the download failed. Download it manually from https://wp-cli.org/ and place it in the project root.",
        );
        err.friendly = true;
        throw err;
    }
    return phar;
}

/**
 * Run a wp-cli i18n command.
 *
 * @param {string[]} args Command arguments.
 * @param {string}   label Log label.
 */
async function wpCliI18n(args, label) {
    const phar = await ensureWpCli();
    const result = await spawnAsync(
        "php",
        [config.wpCli, "i18n", ...args, "--no-color"],
        label,
        false,
    );
    if (result.code !== 0) {
        const err = new Error(
            `${label}\n${result.stderr.trim() || result.stdout.trim()}`,
        );
        err.friendly = true;
        throw err;
    }
}

/**
 * Compare two .pot/.po contents ignoring volatile metadata
 * (PO-Revision-Date, POT-Creation-Date, X-Generator) so a
 * regeneration that only touched metadata does not overwrite
 * the file (keeps diffs and VCS history clean).
 *
 * @param {string} a First file content.
 * @param {string} b Second file content.
 * @return {boolean} True when contents are equal ignoring metadata.
 */
function equalIgnoringMeta(a, b) {
    const strip = (s) =>
        s
            .split("\n")
            .filter(
                (line) =>
                    !/^"POT-Creation-Date:/i.test(line) &&
                    !/^"PO-Revision-Date:/i.test(line) &&
                    !/^"X-Generator:/i.test(line) &&
                    !/^"Project-Id-Version:.*PLUGIN_VERSION/i.test(line),
            )
            .join("\n");
    return strip(a) === strip(b);
}

/**
 * Build the .pot file and update all .po files.
 *
 * @param {boolean} makeMo Also compile .mo files (build:github).
 */
export async function makePot(makeMo = false) {
    const potFile = join(config.languagesDir, `${config.textdomain}.pot`);
    const excludes = "release,node_modules,.git,.opencode,bundler,tests";
    /**
     * Keep the previous .pot around: if the regeneration only changes
     * metadata (creation date, generator, version timestamp), restore
     * the original file instead of polluting the diff.
     */
    const potBackup = potFile + ".makepot.bak";
    const hadPot = existsSync(potFile);
    const potBefore = hadPot ? readFileSync(potFile, "utf8") : null;
    if (hadPot) {
        const { copyFileSync } = await import("node:fs");
        copyFileSync(potFile, potBackup);
    }
    await wpCliI18n(
        [
            "make-pot",
            ".",
            potFile,
            // Sources use the 'PLUGIN_NAME' textdomain placeholder — without
            // --ignore-domain wp-cli would filter them out by domain.
            "--ignore-domain",
            `--exclude=${excludes}`,
            `--slug=${config.textdomain}`,
        ],
        "make-pot",
    );
    if (hadPot && equalIgnoringMeta(potBefore, readFileSync(potFile, "utf8"))) {
        renameSync(potBackup, potFile);
    } else if (hadPot) {
        unlinkSync(potBackup);
    }
    const poFiles = existsSync(join(root, config.languagesDir))
        ? readdirSync(join(root, config.languagesDir)).filter((f) =>
              f.endsWith(".po"),
          )
        : [];
    for (const po of poFiles) {
        await wpCliI18n(
            ["update-po", join(config.languagesDir, po)],
            `update-po ${po}`,
        );
    }
    if (makeMo) {
        if (poFiles.length) {
            await wpCliI18n(["make-mo", config.languagesDir], "make-mo");
        } else {
            log("make-mo — no .po files found, skipped", "w");
        }
    }
    log(
        `i18n — ${potFile}${poFiles.length ? ` + ${poFiles.length} .po updated` : ""}${makeMo && poFiles.length ? " + .mo compiled" : ""}`,
        "s",
    );
}
