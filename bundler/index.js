#!/usr/bin/env bun
/**
 * Bundler entry point — replaces the old Grunt pipeline.
 *
 * Modes:
 *   dev (default) — watcher, compile on change (no ZIP, no i18n)
 *   compile       — one-shot asset compile (old `grunt` default task)
 *   build         — build:github, then build:wporg, then Plugin Check (PCP)
 *   build:wporg   — production build → release/wporg/{name}.zip
 *   build:github  — production build + .mo → release/github/{name}.zip
 *   make-pot      — translations only (.pot + .po update)
 *   update-libs   — git pull on the vendored iWorks libraries (submodules)
 *   check:pcp     — Plugin Check (PCP) on the built wp.org ZIP (stages + cleans up)
 *   test          — PHPUnit (tests/php/phpunit.xml)
 *   notes         — release notes checklist (grunt parity)
 */

import { dirname, join, resolve } from "node:path";
import { existsSync, watch } from "node:fs";
import { executionTime, log } from "./consoleHelpers.mjs";
import { ProgressBar } from "./progressBar.mjs";
import { spawnAsync } from "./spawnAsync.mjs";
import { config } from "./config.mjs";
import { buildJS, formatSources, cleanCompiled } from "./pipelines.mjs";
import { prettierFile } from "./tools.mjs";
import { makeZip } from "./makeZip.mjs";
import { makePot } from "./makePot.mjs";
import { pluginCheck } from "./pluginCheck.mjs";

const root = resolve(dirname(new URL(import.meta.url).pathname), "..");

/**
 * Friendly error printer — one-liners, no stack traces.
 *
 * @param {*} error Thrown error.
 */
function printError(error) {
	const message = error?.friendly
		? error.message
		: error?.stack || String(error);
	log(message, "e");
}

/**
 * One-shot asset compile (JS).
 *
 * @param {object} options
 * @param {boolean} options.format Run the prettier pass first.
 */
async function compileAssets({ format = false } = {}) {
	if (format) {
		await formatSources();
	}
	await buildJS();
}

/**
 * Production build for one flavor.
 *
 * @param {string} flavor "wporg" or "github".
 */
async function build(flavor) {
	const start = Date.now();
	try {
		const steps = 5;
		const bar = new ProgressBar(steps);
		bar.tick("clean");
		await cleanCompiled();
		bar.tick("prettier");
		await formatSources();
		bar.tick("js");
		await buildJS();
		bar.tick("i18n");
		await makePot(flavor === "github");
		bar.tick("zip");
		await makeZip(flavor);
		log(
			`BUILD OK (${flavor}) in ${((Date.now() - start) / 1000).toFixed(2)}s`,
			"s",
		);
	} catch (error) {
		log("BUILD FAILED", "e");
		printError(error);
		process.exit(1);
	}
}

/**
 * Dev watcher — compile changed assets, no ZIP, no i18n.
 */
function dev() {
	const exitAfterMs = Bun.env.DEV_EXIT_AFTER_MS || "";
	if (exitAfterMs) {
		setTimeout(() => {
			log(
				`dev — exiting after ${exitAfterMs} ms (DEV_EXIT_AFTER_MS)`,
				"i",
			);
			process.exit(0);
		}, Number(exitAfterMs));
	}
	log(
		"dev — watching for changes (no auto browser reload, refresh manually)",
		"i",
	);

	let timer = null;
	const queue = new Set();

	const processQueue = async () => {
		timer = null;
		const changed = [...queue];
		queue.clear();
		const js = changed.some(
			(f) => f.startsWith("assets/scripts/src/") && f.endsWith(".js"),
		);
		if (!js) {
			return;
		}
		try {
			for (const file of changed.filter(
				(f) => f.startsWith("assets/scripts/src/") && f.endsWith(".js"),
			)) {
				await prettierFile(join(root, file));
			}
			await compileAssets();
			log(`dev — rebuilt (${changed.length} changed)`, "s");
		} catch (error) {
			printError(error);
		}
	};

	watch(root, { recursive: true }, (event, filename) => {
		if (!filename) {
			return;
		}
		const rel = filename.replace(/\\/g, "/");
		const relevant =
			rel.startsWith("assets/scripts/src/") && rel.endsWith(".js");
		if (!relevant) {
			return;
		}
		queue.add(rel);
		if (!timer) {
			timer = setTimeout(processQueue, 200);
		}
	});
}

/**
 * Update vendored iWorks libraries (git submodules).
 */
async function updateLibs() {
	const libs = [
		["includes/iworks/options", "iWorks Options"],
		["includes/iworks/rate", "iWorks Rate"],
	];
	let failed = 0;
	for (const [dir, label] of libs) {
		if (!existsSync(join(root, dir))) {
			log(
				`${label} — ${dir} not found, skipped (git submodule add?)`,
				"w",
			);
			continue;
		}
		const result = await spawnAsync(
			"git",
			["pull"],
			`git pull ${dir}`,
			false,
		);
		if (result.stdout.trim()) {
			console.log(result.stdout.trim());
		}
		if (result.code !== 0) {
			failed++;
		}
	}
	if (failed) {
		const err = new Error(`${failed} library update(s) failed`);
		err.friendly = true;
		throw err;
	}
	log("update-libs — all iWorks libraries updated", "s");
}

/**
 * PHPUnit (grunt `test` parity, jshint dropped).
 */
async function test() {
	const result = await spawnAsync(
		"phpunit",
		["--configuration", "tests/php/phpunit.xml", "--colors=always"],
		"PHPUnit",
		false,
	);
	if (result.stdout.trim()) {
		console.log(result.stdout.trim());
	}
	if (result.code !== 0) {
		log(
			"If phpunit is not installed, set up a WordPress test environment (see tests/php/bootstrap.php).",
			"w",
		);
		process.exit(result.code);
	}
	log("PHPUnit passed", "s");
}

// ─── Mode dispatch ────────────────────────────────────────────────────────────

const mode = process.argv[2] || "dev";

const modes = {
	dev: () => dev(),
	compile: () =>
		executionTime("compile", () =>
			compileAssets({ maps: true, strip: false, format: false }),
		),
	build: async () => {
		await build("github");
		await build("wporg");
		const code = await pluginCheck({
			skipBuild: true,
			optional: true,
			quiet: true,
		});
		log(
			code === 0
				? "BUILD OK (all flavors + pcp)"
				: "BUILD FAILED (pcp errors) — see pcp-check.log",
			code === 0 ? "s" : "e",
		);
		if (code !== 0) {
			process.exit(code);
		}
	},
	"build:wporg": () => build("wporg"),
	"build:github": () => build("github"),
	"make-pot": () => executionTime("make-pot", () => makePot(false)),
	"update-libs": () => executionTime("update-libs", () => updateLibs()),
	"check:pcp": () =>
		pluginCheck({
			skipBuild: process.argv.includes("--skip-build"),
			ignoreWarnings: process.argv.includes("--ignore-warnings"),
		}).then((code) => {
			if (code !== 0) {
				process.exit(code);
			}
		}),
	test: () => test(),
	notes: () => notes(),
};

if (!modes[mode]) {
	log(
		`Unknown mode "${mode}". Available: ${Object.keys(modes).join(", ")}`,
		"e",
	);
	process.exit(1);
}

try {
	await modes[mode]();
} catch (error) {
	printError(error);
	process.exit(1);
}

/**
 * Release notes checklist (grunt `notes` task parity).
 */
function notes() {
	log("Release notes", "i");
	const steps = [
		"1. Check FORUM for open threads",
		"2. REPLY to forum threads + unsubscribe",
		"3. Update the TRANSLATION files",
		"4. Generate ARCHIVE",
		"5. Check ARCHIVE structure - it should be a folder with plugin name",
		"6. INSTALL on a clean WordPress installation",
		"7. RELEASE the plugin on WordPress.org!",
		"8. Add git tag!",
		"9. RELEASE the plugin on GitHub!",
		"10. RELEASE the plugin!",
	];
	for (const step of steps) {
		log(`  ${step}`);
	}
}
