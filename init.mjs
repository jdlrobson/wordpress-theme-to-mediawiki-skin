import { exec } from 'child_process';
import fs from 'fs';
import { buildSkin } from 'mediawiki-skins-cli';
import domino from 'domino';
import unminifySource from 'unminify';
import cssunminifier from 'cssunminifier';
import packageJSON from './package.json' assert { type: "json" };
import postcss from 'postcss';
import sortMediaQueries from 'postcss-sort-media-queries';

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

function mw_the_category() {
    return `{{#data-portlets}}
    {{>CategoryPortlet}}
    {{/data-portlets}}
`;
}

function mw_add_category_if_missing( content ) {
    const locations = [
        '<!-- dynamic_sidebar:sidebar-1-->',
        '<!-- placeholder:categories -->',
    ];

    while (
        content.indexOf('{{#data-portlets.data-category-normal.array-items}}') === -1 &&
        content.indexOf('{{>CategoryPortlet}}') === -1 &&
        content.indexOf('{{>CategoryPlain}}' ) === -1 &&
        content.indexOf('{{>CategoryLinks}}' ) === -1 &&
        locations.length > 0
    ) {
        const location = locations.pop();
        content = content.replace( location, mw_the_category() );
    }
    return content;
}

function add_last_modified( str ) {
    return str.replaceAll(
        '<!-- placeholder:thedate -->',
        '{{>LastModifiedLine}}'
    ).replaceAll(
        '{{lastmodified-get_author_posts_url}}', '?action=history'
    );
}

function getCopyright( text ) {
    return '<span>{{>CopyrightLine}}' + text + '</span>';
}

function getDeepestChildInternal( node, tag, depth = 0 ) {
    const childNodes = Array.from(node.childNodes)
        .filter((child) => child.nodeType !== 3 && child.nodeType !== 8 &&
            child.tagName === tag
        );

    if ( childNodes.length === 0 ) {
        return { child: node, depth };
    }
    const nodes = childNodes
        .map((child) => {
            return getDeepestChildInternal(child, tag, depth + 1);
        }).sort((n, n2) => n.depth > n2.depth ? -1 : 1 )
    return nodes[0];
}
function getDeepestChild(node, tag ) {
    const r = getDeepestChildInternal( node, tag, 0 );
    return r.child;
}

function addCopyright(doc, template) {
    const hasNoFooterLinks = template.indexOf('#data-footer.data-places') === -1;
    const noCopyright = template.indexOf('#data-footer.data-info') === -1 &&
        template.indexOf( '{{>CompactFooter}}' ) === -1;
    let done = false;
    if ( noCopyright ) {
        if ( hasNoFooterLinks ) {
            // TODO: No footer inside Hello Elementor, Kadence, Neve
            const footer = doc.querySelector('footer');
            if ( footer ) {
                const slot = footer.querySelector( '.site-container, .container') ||
                    getDeepestChild(footer, 'DIV');
                if ( slot ) {
                    slot.innerHTML = '';
                    slot.appendChild(
                        doc.createTextNode(
                            '{{>CompactFooter}}'
                        )
                    );
                    return;
                }
            }
        }
        Array.from( doc.querySelectorAll( 'footer' ) ).forEach((node) => {
            const text = node.textContent;
            if (
                // 2020,
                text.indexOf( '©' ) > -1 ||
                // 2021
                text.indexOf( 'Proudly powered by' ) > -1 ||
                // OceanWP
                text.indexOf( 'Copyright -' ) > -1
            ) {
                Array.from( node.querySelectorAll( '*' ) ).forEach((childNode) => {
                    const trimText = ( childNode.textContent ).trim();
                    if (
                        !done &&
                        trimText.indexOf( 'Copyright -' ) === 0 ||
                        trimText.indexOf( 'Proudly powered by' ) === 0 ||
                        trimText.indexOf( '©' ) === 0
                    ) {
                        done = true;
                        childNode.innerHTML = getCopyright(
                            trimText
                        );
                    }
                });
            }
        });
    }
}

function injectLanguageButton(doc, template) {
    const noLanguages = template.indexOf( 'data-portlets.data-language' ) === -1;
    const title = doc.querySelector(
        'h1.entry-title, h2.entry-title, h1.card_title'
    );
    if ( noLanguages && title ) {
        title.parentNode.insertBefore(
            doc.createTextNode( '{{#is-article}}{{>LanguageButton}}{{/is-article}}' ),
            title.nextSibling
        );
    }
}
function replacePlaceholderTags( doc ) {
    doc.querySelectorAll( '.mw-category-placeholder' ).forEach((node) => {
        node.replaceWith(
            doc.createTextNode( '{{#data-portlets.data-category-normal.array-items}}{{{html}}}{{/data-portlets.data-category-normal.array-items}}')
        )
    })
}

function wrapMetaData( doc ) {
    // nv- prefixed are for Neve skin
    doc.querySelectorAll( [
        '.entry-meta',
        '.post-tags',
        '.nv-tags-list',
        '.nv-meta-list',
        '.entry-footer',
        '.meta',
        '.cat-links',
        '.entry-categories'
    ].join( ',' ) ).forEach(( node ) => {
        const before = doc.createTextNode( '{{^is-specialpage}}' );
        const after = doc.createTextNode( '{{/is-specialpage}}' );
        node.parentNode.insertBefore( before, node );
        node.parentNode.insertBefore( after, node.nextSibling );
    });
}

function cleanupTemplateWithDomino(template) {
    const window = domino.createWindow(template);
    const doc = window.document;
    injectLanguageButton(doc, template);
    addCopyright(doc, template);
    replacePlaceholderTags(doc);
    wrapMetaData(doc);
    // See Neve skin
    const nodes = doc.querySelectorAll('.mw-wordpress-category-cleanup');
    nodes.forEach((node, i) => {
        if ( i === 0 ) {
            node.parentNode.insertBefore(
                doc.createTextNode( '{{>CategoryPlain}}' ),
                node
            );
        }
        node.parentNode.removeChild(node);
    });

    Array.from(doc.querySelectorAll('time')).forEach((timeNode) => {
        if ( timeNode.textContent === '<!-- placeholder:thedate -->' ) {
            timeNode.textContent ='init.mjs placeholder the date';
        }
    })
    doc.querySelectorAll( '[datetime], time[content]' ).forEach((node) => {
        node.removeAttribute('datetime');
        node.removeAttribute('content');
    });

    let final = doc.body.innerHTML.replace(
        /\{\{&gt;/g, '{{>'
    );
    final = add_last_modified(final);
    final = final.replaceAll(
        '#placeholder:pageinfo', '?action=info'
    );
    final = mw_add_category_if_missing(final);
    return final;
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
            console.log(`Finalizing ${skinName}`)
            const mustache = cleanupTemplateWithDomino(
                fs.readFileSync(MUSTACHE_PATH).toString()
            );
            meta.bodyClasses.push( 'single-post' );
            meta.bodyClasses.push( 'single' );
            buildSkin(
                skinNameCamelCase,
                mustache,
                cssunminifier.unminify(fs.readFileSync(LESS_PATH).toString(), 2).toString(),
                `// Disable module exporting in this context
module = undefined; exports = undefined;
${unminifySource( fs.readFileSync(JS_PATH).toString() )}`,
                '',
                {
                    Zipper,
                    CustomFileSaver: FSSaver,
                    isCSS: true,
                    skinOptions: Object.assign( meta, {
                        toc: false
                    } ),
                    authors: meta.authors,
                    license: 'GPL-2.0-or-later',
                    messages: {
                        en: Object.assign( messagesEnglish, {
                            [`${skinKey}-skin-desc`]: `A port of the Wordpress ${skinName} theme (version ${meta.version}).
Built via [https://github.com/jdlrobson/wordpress-theme-to-mediawiki-skin wordpress-theme-to-mediawiki-skin@${packageJSON.version}].
Last updated on ${new Date().toDateString()}.`,
                            [`${skinKey}-no-categories`]: 'Uncategorized'
                        } )
                    },
                    skinFeatures: {
                        "content-links": false,
                        "content-media": true,
                        'content-tables': true,
                        'interface-category': mustache.indexOf( '{{>CategoryPortlet}}') > -1 ? true : undefined,
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
            // post CSS
            const cssPath = `${ folder  }/resources/common.css`;
            if ( fs.existsSync( cssPath ) ) {
                //postcss --use autoprefixer -o main.css css/*.css
                const newCss = postcss( [
                    sortMediaQueries({
                      sort: 'mobile-first' // default
                    })
                ] ).process(
                    fs.readFileSync( cssPath ).toString()
                ).then( ( result ) => {
                    console.log('Processing complete');
                    if ( result.css ) {
                        console.log('Write postcss');
                        fs.writeFile( cssPath , result.css, () => true)
                      }
                } );
            } else {
                console.log( `Unable to locate ${ cssPath }` );
            }
        });
        //exec( `open http://localhost:8888/w/index.php/Selenium_category_test?useskin=${skinName}`)
    }
});
