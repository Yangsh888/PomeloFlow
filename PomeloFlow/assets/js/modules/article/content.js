import { $, $$ } from '../../core/utils.js';

let cachedRoot = null;
let cachedContext = null;

export function getContentRoot() {
  return $('#pf-post-content');
}

function buildContentContext(root) {
  const images = $$('img', root);
  const preBlocks = $$('pre', root);
  const pickTextHeadings = selector => $$(selector, root).filter(node => (node.textContent || '').trim() !== '');
  const primaryHeadings = pickTextHeadings('h2,h3,h4,h5');
  const headings = primaryHeadings.length ? primaryHeadings : pickTextHeadings('h1,h2,h3,h4,h5');

  return {
    root,
    images,
    preBlocks,
    headings,
  };
}

export function getContentContext() {
  const root = getContentRoot();
  if (!root) {
    cachedRoot = null;
    cachedContext = null;
    return null;
  }

  if (cachedRoot === root && cachedContext) {
    return cachedContext;
  }

  cachedRoot = root;
  cachedContext = buildContentContext(root);
  return cachedContext;
}
