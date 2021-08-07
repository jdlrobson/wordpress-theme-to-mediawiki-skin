import { exec } from 'child_process';
import fs from 'fs';
import { buildSkin } from 'mw-skin-builder';

// run nvm use if getting error here
global.__dirname = import.meta.url.replace( '/index.js', '').replace(
	'file://', ''
);

const __dirname = import.meta.url.replace( '/init.mjs', '').replace(
	'file://', ''
);

const ROOT = `${__dirname}/WPMediaWikiSkins/`;
if ( fs.existsSync(ROOT) ) {
    fs.rmdirSync(ROOT, { recursive: true });
}
fs.mkdirSync(ROOT);

const dir = fs.readdirSync( `${__dirname}/themes/`, {
    withFileTypes: true
} );

function FSSaver() {
    return ( content, zipName ) => {
        // Already done.
    };
}
class Zipper {
    constructor(root) {
        this.root = root || '';
    }
    generateAsync() {
        return Promise.resolve( this );
    }
    file( f, content ) {
        fs.writeFileSync( `${ROOT}/${this.root}/${f}`, content );
    }
    folder( folderName ) {
        fs.mkdirSync(`${ROOT}/${this.root}/${folderName}`);
        return new Zipper( `${this.root}/${folderName}` );
    }
}

function camelcase( str ) {
	return str.replace( /(?:^\w|[A-Z]|\b\w)/g, function ( word ) {
		return word.toUpperCase();
	} ).replace( /\s+/g, '' );
}

const OUTPUT_DIR = `${__dirname}/output`;

if ( !fs.existsSync( OUTPUT_DIR ) ) {
    fs.mkdirSync( OUTPUT_DIR );
}

dir.forEach((file) => {
    if ( file.isDirectory() ) {
        const skinName = file.name;
        console.log(`Building ${skinName}`)
        exec(`php convert.php ${skinName} disableErrors`, (error, stdout, stderr) => {
            if (stderr) {
                console.error(`stderror: ${error} ${stderr}`);
                return;
            }
            const outFolder = `${OUTPUT_DIR}/${skinName}`;
            const MUSTACHE_PATH = `${outFolder}/skin.mustache`;
            const LESS_PATH = `${outFolder}/skin.css`;
            const JS_PATH = `${outFolder}/skin.js`;
            const META_PATH = `${outFolder}/meta.json`;
            const MSG_PATH = `${outFolder}/en.json`;
            const skinNameCamelCase = camelcase( skinName );
            const messagesEnglish = JSON.parse(
                fs.readFileSync(
                    MSG_PATH
                ).toString()
            );
            const skinKey = skinName.toLowerCase();

            const meta = JSON.parse(
                fs.readFileSync(META_PATH).toString()
            );
            messagesEnglish[`${skinKey}-skin-desc`] = `A port of the Wordpress ${skinName} theme (version ${meta.version}).
Built via [https://skins.wmflabs.org skins.wmflabs.org].
Last updated on ${new Date().toDateString()}.`;
            buildSkin(
                skinNameCamelCase,
                fs.readFileSync(MUSTACHE_PATH).toString(),
                fs.readFileSync(LESS_PATH).toString(),
                fs.readFileSync(JS_PATH).toString(),
                '',
                {
                    Zipper,
                    CustomFileSaver: FSSaver,
                    isCSS: true,
                    skinOptions: Object.assign( meta, {
                        toc: false
                    } ),
                    license: 'GPL-2.0-or-later',
                    messages: {
                        en: messagesEnglish
                    },
                    skinFeatures: {
                        "content-links": true,
                        "content-media": true,
                        'content-tables': true,
                        'interface-message-box': true,
                        'toc': true
                    }
                }
            );
            fs.rmSync(META_PATH);
            fs.rmSync(MUSTACHE_PATH);
            fs.rmSync(LESS_PATH);
            fs.rmSync(MSG_PATH);
            fs.rmSync(JS_PATH);
            const folder = `${ROOT}${skinNameCamelCase}`;
            exec(`mv ${outFolder}/resources/* ${folder}/resources`, () => {
                exec(`mv ${outFolder}/* ${folder}/resources`, () => {
                    const destinationFolder = `/Users/jrobson/git/MediaWikiWordpressThemes/`;
                    const destination = `${destinationFolder}${skinNameCamelCase}`;
                    const command = `rm -rf ${destination} && mv ${folder} ${destinationFolder}`;
                    console.log(command);
                    exec(command);
                });
            });
        });
    }
});
