// Rewrites public/js in place. The Docker asset stage is the only caller.
// Local sources stay readable unless OPTIMIZE_PUBLIC_JS=1.

import { build, transform } from "esbuild";
import { readdir, readFile, writeFile } from "node:fs/promises";
import path from "node:path";

if (process.env.OPTIMIZE_PUBLIC_JS !== "1") {
    console.error(
        "Refusing to rewrite public/js. This step runs in the deploy image only.",
    );
    process.exit(1);
}

const dir = path.resolve(process.argv[2] ?? "public/js");
const entryName = "index.js";
const entryPath = path.join(dir, entryName);

const names = (await readdir(dir))
    .filter((name) => name.endsWith(".js"))
    .sort();

if (!names.includes(entryName)) {
    console.error(`Missing ${entryPath}`);
    process.exit(1);
}

const sources = new Map();
for (const name of names) {
    sources.set(name, await readFile(path.join(dir, name)));
}

const shared = {
    target: "es2022",
    charset: "utf8",
    legalComments: "none",
    minify: true,
};

// Bundle the page entry, and also minify each module in place.
// A browser can still be holding the previous index.js, which imports these files.
const bundled = await build({
    ...shared,
    entryPoints: [entryPath],
    bundle: true,
    write: false,
    format: "esm",
    platform: "browser",
    sourcemap: false,
    treeShaking: true,
});

if (bundled.outputFiles.length !== 1) {
    console.error("Expected a single bundled entry for public/js/index.js");
    process.exit(1);
}

let before = 0;
let after = 0;

for (const name of names) {
    const source = sources.get(name);
    before += source.length;

    const code =
        name === entryName
            ? bundled.outputFiles[0].text
            : (
                  await transform(source.toString("utf8"), {
                      ...shared,
                      loader: "js",
                      sourcefile: name,
                  })
              ).code;

    const file = path.join(dir, name);
    await writeFile(file, code);
    const written = Buffer.byteLength(code);
    after += written;
    const note = name === entryName ? " bundled" : "";
    console.log(`${name} ${source.length} -> ${written}${note}`);
}

console.log(`public/js ${before} -> ${after}`);
