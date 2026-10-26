import Chart from 'chart.js/auto';
import katex from 'katex';
import renderMathInElement from 'katex/contrib/auto-render';
import 'katex/dist/katex.min.css';

window.Chart = Chart;
window.katex = katex;
window.renderMathInElement = renderMathInElement;

/**
 * Render math in a DOM element using KaTeX.
 * Supports inline math ($...$ or \(...\)) and display math ($$...$$ or \[...\]).
 */
export function renderMath(element) {
    if (!element) return;
    try {
        renderMathInElement(element, {
            delimiters: [
                { left: '$$', right: '$$', display: true },
                { left: '$', right: '$', display: false },
                { left: '\\(', right: '\\)', display: false },
                { left: '\\[', right: '\\]', display: true }
            ],
            throwOnError: false,
        });
    } catch (e) {
        console.warn('KaTeX render error:', e);
    }
}

window.renderMath = renderMath;

// Automatically render inline/display math on any elements with .ctc-markdown-content
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.ctc-markdown-content').forEach(el => renderMath(el));
    });
} else {
    document.querySelectorAll('.ctc-markdown-content').forEach(el => renderMath(el));
}

