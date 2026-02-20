import { nextTick } from 'vue';

const markdownActions = {
    bold: { prefix: '**', suffix: '**', placeholder: 'жирный текст' },
    italic: { prefix: '*', suffix: '*', placeholder: 'курсив' },
    strikethrough: { prefix: '~~', suffix: '~~', placeholder: 'зачёркнутый' },
    code: { prefix: '`', suffix: '`', placeholder: 'код' },
    codeBlock: { prefix: '```\n', suffix: '\n```', placeholder: 'блок кода', block: true },
    list: { prefix: '- ', suffix: '', placeholder: 'элемент списка', line: true },
    orderedList: { prefix: '1. ', suffix: '', placeholder: 'элемент списка', line: true },
    quote: { prefix: '> ', suffix: '', placeholder: 'цитата', line: true },
};

export function useMarkdownToolbar() {
    function applyMarkdown(textareaRef, formMessageRef, type) {
        const textarea = textareaRef.value;
        if (!textarea) return;

        const action = markdownActions[type];
        if (!action) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = formMessageRef.value;
        const selectedText = text.substring(start, end);

        let before = text.substring(0, start);
        let after = text.substring(end);
        let insertText;
        let cursorStart;
        let cursorEnd;

        if (action.line || action.block) {
            // Для строчных элементов — добавляем перенос строки если нужно
            const needsNewlineBefore = before.length > 0 && !before.endsWith('\n');
            const linePrefix = needsNewlineBefore ? '\n' : '';

            if (selectedText) {
                if (action.line) {
                    // Оборачиваем каждую строку
                    const lines = selectedText.split('\n');
                    insertText = linePrefix + lines.map(l => action.prefix + l).join('\n') + action.suffix;
                } else {
                    insertText = linePrefix + action.prefix + selectedText + action.suffix;
                }
                cursorStart = start + insertText.length;
                cursorEnd = cursorStart;
            } else {
                const placeholder = action.placeholder;
                if (action.line) {
                    insertText = linePrefix + action.prefix + placeholder + action.suffix;
                    cursorStart = start + linePrefix.length + action.prefix.length;
                    cursorEnd = cursorStart + placeholder.length;
                } else {
                    insertText = linePrefix + action.prefix + placeholder + action.suffix;
                    cursorStart = start + linePrefix.length + action.prefix.length;
                    cursorEnd = cursorStart + placeholder.length;
                }
            }
        } else {
            // Инлайн-элементы
            if (selectedText) {
                insertText = action.prefix + selectedText + action.suffix;
                cursorStart = start + insertText.length;
                cursorEnd = cursorStart;
            } else {
                const placeholder = action.placeholder;
                insertText = action.prefix + placeholder + action.suffix;
                cursorStart = start + action.prefix.length;
                cursorEnd = cursorStart + placeholder.length;
            }
        }

        formMessageRef.value = before + insertText + after;

        nextTick(() => {
            textarea.focus();
            textarea.setSelectionRange(cursorStart, cursorEnd);
        });
    }

    return { applyMarkdown };
}
