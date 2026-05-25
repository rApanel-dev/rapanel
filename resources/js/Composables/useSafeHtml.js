import DOMPurify from 'dompurify'

const NEWS_CONFIG = {
    ALLOWED_TAGS: [
        'p', 'br', 'strong', 'em', 'u', 's', 'b', 'i',
        'ul', 'ol', 'li',
        'h2', 'h3', 'h4',
        'a', 'img',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'hr', 'div', 'span',
    ],
    ALLOWED_ATTR: ['href', 'src', 'alt', 'title', 'class', 'target', 'rel', 'width', 'height'],
    ALLOW_DATA_ATTR: false,
    FORCE_BODY: true,
}

// Tags limitados para descripciones de ítems del juego (no links, no iframes)
const ITEM_DESC_CONFIG = {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'br', 'span', 'ul', 'ol', 'li', 'p', 'color'],
    ALLOWED_ATTR: ['class', 'style'],
    ALLOW_DATA_ATTR: false,
}

export function useSafeHtml() {
    const sanitizeNews = (html) => DOMPurify.sanitize(html ?? '', NEWS_CONFIG)
    const sanitizeItemDesc = (html) => DOMPurify.sanitize(html ?? '', ITEM_DESC_CONFIG)

    return { sanitizeNews, sanitizeItemDesc }
}
