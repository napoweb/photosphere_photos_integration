/* Minimal, safe Markdown renderer for public 360°-tour descriptions.
 * No raw HTML or arbitrary protocols: all text is inserted as text nodes.
 */
(() => {
  'use strict';

  function appendInline(parent, source) {
    const pattern = /\[([^\]\n]{1,160})\]\(([^)\n]{1,2000})\)|\*\*([^*\n]+)\*\*|\*([^*\n]+)\*/g;
    let offset = 0;
    for (const match of source.matchAll(pattern)) {
      if (match.index > offset) parent.append(document.createTextNode(source.slice(offset, match.index)));
      if (match[1] !== undefined) {
        const rawUrl = match[2].trim();
        let valid = false;
        try {
          const url = new URL(rawUrl);
          valid = (url.protocol === 'https:' || url.protocol === 'http:')
            && !/\s/.test(rawUrl) && !url.username && !url.password;
        } catch (_e) { /* Invalid URLs remain visible as plain text. */ }
        if (valid) {
          const anchor = document.createElement('a');
          anchor.href = rawUrl;
          anchor.target = '_blank';
          anchor.rel = 'noopener noreferrer nofollow ugc';
          anchor.referrerPolicy = 'no-referrer';
          anchor.textContent = match[1];
          parent.append(anchor);
        } else {
          parent.append(document.createTextNode(match[0]));
        }
      } else if (match[3] !== undefined) {
        const strong = document.createElement('strong');
        strong.textContent = match[3];
        parent.append(strong);
      } else {
        const emphasis = document.createElement('em');
        emphasis.textContent = match[4];
        parent.append(emphasis);
      }
      offset = match.index + match[0].length;
    }
    if (offset < source.length) parent.append(document.createTextNode(source.slice(offset)));
  }

  function render(target, source) {
    if (!target) return;
    target.replaceChildren();
    let list = null;
    let listTag = '';
    let paragraph = null;
    for (const line of String(source ?? '').replace(/\r\n?/g, '\n').split('\n')) {
      const trimmed = line.trim();
      if (trimmed === '') { list = null; listTag = ''; paragraph = null; continue; }
      const unordered = /^[-+*]\s+(.+)$/.exec(trimmed);
      const ordered = /^\d{1,3}[.)]\s+(.+)$/.exec(trimmed);
      if (unordered || ordered) {
        const nextTag = ordered ? 'ol' : 'ul';
        if (!list || listTag !== nextTag) {
          list = document.createElement(nextTag);
          target.append(list);
          listTag = nextTag;
        }
        const item = document.createElement('li');
        appendInline(item, (ordered || unordered)[1]);
        list.append(item);
        paragraph = null;
      } else {
        list = null;
        listTag = '';
        if (!paragraph) {
          paragraph = document.createElement('p');
          target.append(paragraph);
        } else {
          paragraph.append(document.createElement('br'));
        }
        appendInline(paragraph, trimmed);
      }
    }
  }

  window.PPI_TOUR_DESCRIPTION = Object.freeze({render});
})();
