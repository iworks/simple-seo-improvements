/**
 * Asset pipeline — JS only (concat → terser).
 * This plugin ships no CSS build (nothing to compile); the output path and
 * file name mirror the old Grunt pipeline exactly (the PHP code enqueues
 * these files — never change them).
 */

import { dirname, join, resolve } from "node:path";
import { existsSync, readdirSync } from "node:fs";
import { log } from "./consoleHelpers.mjs";
import { config } from "./config.mjs";
import {
    concatFiles,
    minifyJS,
    writeFileAtomic,
    prettierSources,
    cleanGlob,
} from "./tools.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

/**
 * Banner prepended to concatenated bundles (grunt `concat` template).
 *
 * @return {string}
 */
function concatBanner() {
    return [
        `/*! ${config.title} - ${config.version}`,
        ` * ${config.website}`,
        ` * Copyright (c) ${config.buildYear}`,
        ` * Licensed ${config.license} */`,
        "",
    ].join("\n");
}

/**
 * Banner prepended to minified bundles (grunt `uglify` template — note the `;`).
 *
 * @return {string}
 */
function minBanner() {
    return [
        `/*! ${config.title} - ${config.version}`,
        ` * ${config.website}`,
        ` * Copyright (c) ${config.buildYear};`,
        ` * Licensed ${config.license} */`,
        "",
    ].join("\n");
}

/**
 * List files in a directory (no recursion), sorted, filtered by extension.
 *
 * @param {string}   dir Absolute directory.
 * @param {string[]} extensions Allowed extensions (with dot).
 * @return {string[]} Absolute file paths.
 */
function listFiles(dir, extensions) {
    if (!existsSync(dir)) {
        return [];
    }
    return readdirSync(dir, { withFileTypes: true })
        .filter((e) => e.isFile() && extensions.includes(ext(e.name)))
        .map((e) => join(dir, e.name))
        .sort();
}

function ext(name) {
    const idx = name.lastIndexOf(".");
    return idx === -1 ? "" : name.slice(idx);
}

/**
 * JS pipeline: concat sources per-variant → terser minify.
 *
 * Grunt parity: this plugin enqueues assets/scripts/admin/{name}.js
 * (config.scriptsDir = assets/scripts/admin), so only the admin variant
 * is built.
 */
export async function buildJS() {
    const variant = "admin";
    const srcDir = join(root, config.jsSrcDir, variant);
    const files = listFiles(srcDir, [".js"]);
    if (!files.length) {
        log(
            `js — assets/scripts/src/${variant}/ has no sources, ${variant} bundle is banner-only`,
            "w",
        );
    }
    const concat = await concatFiles(files, concatBanner());
    const outFile = join(root, config.scriptsDir, `${config.name}.js`);
    writeFileAtomic(outFile, concat);
    const min = await minifyJS(concat, minBanner());
    writeFileAtomic(outFile.replace(/\.js$/, ".min.js"), min);
    log("js — admin bundle concatenated and minified", "s");
}

/**
 * Prettier pass over the JS/JSON sources (build pipelines run it first).
 */
export async function formatSources() {
    await prettierSources([
        "assets/scripts/src/**/*.js",
        "bundler/**/*.js",
        "bundler/**/*.mjs",
        "*.js",
    ]);
    log("prettier — sources formatted", "s");
}

/**
 * Remove built files (grunt `clean` semantics).
 */
export async function cleanCompiled() {
    await cleanGlob("assets/scripts/admin/*.js");
    await cleanGlob("languages/*.mo");
    log("clean — built JS and languages/*.mo removed", "i");
}
