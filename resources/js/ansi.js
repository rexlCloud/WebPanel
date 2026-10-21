// Converts ANSI SGR colour codes in console output to HTML. All text is escaped first,
// so console lines (which can contain player chat) can never inject markup.

const BASIC = ['#1e293b', '#f87171', '#4ade80', '#facc15', '#60a5fa', '#e879f9', '#22d3ee', '#e2e8f0'];
const BRIGHT = ['#64748b', '#fca5a5', '#86efac', '#fde047', '#93c5fd', '#f0abfc', '#67e8f9', '#ffffff'];

const escapeHtml = (text) =>
    text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

function color256(n) {
    if (n < 8) return BASIC[n];
    if (n < 16) return BRIGHT[n - 8];
    if (n < 232) {
        const i = n - 16;
        const level = (v) => (v === 0 ? 0 : 55 + v * 40);
        return `rgb(${level(Math.floor(i / 36))},${level(Math.floor(i / 6) % 6)},${level(i % 6)})`;
    }
    const gray = 8 + (n - 232) * 10;
    return `rgb(${gray},${gray},${gray})`;
}

function applyCodes(state, codes) {
    for (let i = 0; i < codes.length; i++) {
        const code = codes[i];
        if (code === 0) {
            state.color = null;
            state.bold = false;
        } else if (code === 1) {
            state.bold = true;
        } else if (code === 22) {
            state.bold = false;
        } else if (code >= 30 && code <= 37) {
            state.color = BASIC[code - 30];
        } else if (code >= 90 && code <= 97) {
            state.color = BRIGHT[code - 90];
        } else if (code === 39) {
            state.color = null;
        } else if (code === 38 && codes[i + 1] === 5) {
            state.color = color256(codes[i + 2] ?? 7);
            i += 2;
        } else if (code === 38 && codes[i + 1] === 2) {
            state.color = `rgb(${codes[i + 2] ?? 0},${codes[i + 3] ?? 0},${codes[i + 4] ?? 0})`;
            i += 4;
        }
    }
}

export function ansiToHtml(line) {
    const state = { color: null, bold: false };
    let html = '';
    // eslint-disable-next-line no-control-regex
    const parts = String(line).split(/\u001b\[([0-9;]*)m/);
    for (let i = 0; i < parts.length; i++) {
        if (i % 2 === 1) {
            applyCodes(state, parts[i] === '' ? [0] : parts[i].split(';').map(Number));
            continue;
        }
        if (parts[i] === '') continue;
        const text = escapeHtml(parts[i]);
        const style = [state.color && `color:${state.color}`, state.bold && 'font-weight:600'].filter(Boolean).join(';');
        html += style ? `<span style="${style}">${text}</span>` : text;
    }
    return html;
}
