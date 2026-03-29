const lines = [
    '███████╗███████╗ ██████╗██████╗ ███████╗████████╗',
    '██╔════╝██╔════╝██╔════╝██╔══██╗██╔════╝╚══██╔══╝',
    '███████╗█████╗  ██║     ██████╔╝█████╗     ██║',
    '╚════██║██╔══╝  ██║     ██╔══██╗██╔══╝     ██║',
    '███████║███████╗╚██████╗██║  ██║███████╗   ██║',
    '╚══════╝╚══════╝ ╚═════╝╚═╝  ╚═╝╚══════╝   ╚═╝',
    '██████╗ ██████╗  ██████╗ ██████╗',
    '██╔══██╗██╔══██╗██╔═══██╗██╔══██╗',
    '██║  ██║██████╔╝██║   ██║██████╔╝',
    '██║  ██║██╔══██╗██║   ██║██╔═══╝',
    '██████╔╝██║  ██║╚██████╔╝██║',
    '╚═════╝ ╚═╝  ╚═╝ ╚═════╝ ╚═╝',
];

const segments = 3;
const maxDiag = lines.length - 1 + segments - 1;

function diagColor(index) {
    const t = index / maxDiag;
    const r = Math.round(139 + (79 - 139) * t);
    const g = Math.round(92 + (70 - 92) * t);
    const b = Math.round(246 + (229 - 246) * t);
    const a = (0.15 + 0.85 * t).toFixed(2);

    return `color:rgba(${r},${g},${b},${a});font-weight:bold`;
}

let banner = '\n';
const styles = [];

for (let row = 0; row < lines.length; row++) {
    const line = lines[row];
    const len = line.length;

    for (let seg = 0; seg < segments; seg++) {
        const start = Math.floor(seg * len / segments);
        const end = Math.floor((seg + 1) * len / segments);

        banner += `%c${line.slice(start, end)}`;
        styles.push(diagColor(row + seg));
    }

    banner += '\n';
}

banner += '\n%cZero-knowledge secret sharing\n%cby Guillaume Orsal';
styles.push('color:#94a3b8;font-size:11px');
styles.push('color:#6366f1;font-size:11px;font-weight:bold');

console.log(banner, ...styles);
