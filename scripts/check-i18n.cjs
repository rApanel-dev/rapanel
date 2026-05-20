#!/usr/bin/env node

const fs   = require('fs');
const path = require('path');

const LANG_DIR  = path.resolve(__dirname, '../lang');
const REFERENCE = 'en';

const colors = {
    reset:  '\x1b[0m',
    bold:   '\x1b[1m',
    red:    '\x1b[31m',
    green:  '\x1b[32m',
    yellow: '\x1b[33m',
    cyan:   '\x1b[36m',
    dim:    '\x1b[2m',
};

const c = (color, text) => `${colors[color]}${text}${colors.reset}`;

function loadJson(file) {
    try {
        return JSON.parse(fs.readFileSync(file, 'utf8'));
    } catch (e) {
        console.error(c('red', `  ERROR reading ${file}: ${e.message}`));
        process.exit(1);
    }
}

function getLocales() {
    return fs.readdirSync(LANG_DIR)
        .filter(f => f.endsWith('.json'))
        .map(f => f.replace('.json', ''))
        .sort();
}

function run() {
    const locales  = getLocales();
    const refFile  = path.join(LANG_DIR, `${REFERENCE}.json`);
    const refKeys  = Object.keys(loadJson(refFile));
    const others   = locales.filter(l => l !== REFERENCE);

    console.log('');
    console.log(c('bold', `  rApanel i18n checker`));
    console.log(c('dim',  `  Reference: ${REFERENCE}.json  (${refKeys.length} keys)`));
    console.log(c('dim',  `  Checking:  ${others.join(', ')}`));
    console.log('');

    let totalMissing = 0;
    let totalExtra   = 0;

    for (const locale of others) {
        const file       = path.join(LANG_DIR, `${locale}.json`);
        const translated = loadJson(file);
        const transKeys  = Object.keys(translated);

        const missing = refKeys.filter(k => !(k in translated));
        const extra   = transKeys.filter(k => !(k in Object.fromEntries(refKeys.map(k => [k, 1]))));

        totalMissing += missing.length;
        totalExtra   += extra.length;

        const status = missing.length === 0
            ? c('green', `✓ ${locale}.json`)
            : c('yellow', `✗ ${locale}.json`);

        console.log(`  ${status}  ${c('dim', `(${transKeys.length}/${refKeys.length} keys)`)}`);

        if (missing.length > 0) {
            console.log(c('red', `    Missing ${missing.length} key${missing.length > 1 ? 's' : ''}:`));
            for (const key of missing) {
                const preview = key.length > 70 ? key.slice(0, 67) + '…' : key;
                console.log(c('dim', `      — "${preview}"`));
            }
        }

        if (extra.length > 0) {
            console.log(c('cyan', `    Extra ${extra.length} key${extra.length > 1 ? 's' : ''} (not in en.json):`));
            for (const key of extra) {
                const preview = key.length > 70 ? key.slice(0, 67) + '…' : key;
                console.log(c('dim', `      + "${preview}"`));
            }
        }

        console.log('');
    }

    // Summary
    if (totalMissing === 0 && totalExtra === 0) {
        console.log(c('green', c('bold', '  ✓ All translations are complete!')));
    } else {
        if (totalMissing > 0) {
            console.log(c('yellow', `  ⚠  ${totalMissing} missing key${totalMissing > 1 ? 's' : ''} across all locales`));
        }
        if (totalExtra > 0) {
            console.log(c('cyan', `  ℹ  ${totalExtra} extra key${totalExtra > 1 ? 's' : ''} not in reference`));
        }
    }

    console.log('');
    process.exit(totalMissing > 0 ? 1 : 0);
}

run();
