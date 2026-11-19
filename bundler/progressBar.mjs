/**
 * Minimal progress bar for the build pipeline.
 */

const WIDTH = 30;

export class ProgressBar {
    /**
     * Last rendered bar line (shared with consoleHelpers.log so log lines
     * during an active build can clear and redraw the bar instead of
     * concatenating onto it).
     */
    static line = "";

    constructor(total) {
        this.total = total;
        this.current = 0;
        this.label = "";
    }

    /**
     * Advance one step.
     *
     * @param {string} label Label of the finished step.
     */
    tick(label = "") {
        this.current += 1;
        this.label = label;
        const ratio = Math.min(this.current / this.total, 1);
        const filled = Math.round(ratio * WIDTH);
        const bar = "█".repeat(filled) + "░".repeat(WIDTH - filled);
        ProgressBar.line = `${bar} ${this.current}/${this.total} ${this.label}`;
        process.stdout.write(`\r\x1b[K${ProgressBar.line}`);
        if (this.current >= this.total) {
            ProgressBar.line = "";
            process.stdout.write("\n");
        }
    }
}
