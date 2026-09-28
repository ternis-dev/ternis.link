/* Background service worker (MV3): context menus + omnibox. */
importScripts('src/api.js');

const MENU_ID = 'ternis-shorten';

chrome.runtime.onInstalled.addListener(() => {
  chrome.contextMenus.create({
    id: MENU_ID,
    title: 'Shorten with ternis.link',
    contexts: ['page', 'link', 'selection'],
  });
});

async function targetFromClick(info, tab) {
  if (info.linkUrl) return info.linkUrl;
  if (info.selectionText && /^https?:\/\//i.test(info.selectionText.trim())) {
    return info.selectionText.trim();
  }
  return info.pageUrl || tab?.url || '';
}

async function notify(title, message) {
  try {
    await chrome.notifications.create({
      type: 'basic',
      iconUrl: 'icons/icon48.png',
      title,
      message,
    });
  } catch { /* notifications are best-effort */ }
}

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  if (info.menuItemId !== MENU_ID) return;
  const destination = await targetFromClick(info, tab);
  if (!/^https?:\/\//i.test(destination)) {
    await notify('ternis.link', 'Nothing to shorten on this target.');
    return;
  }
  try {
    const { shortUrl } = await window.TernisLink.shorten(destination);
    await window.TernisLink.pushHistory({ shortUrl, destination, mode: 'menu' });
    await notify('Short link ready', shortUrl);
  } catch (e) {
    await notify('ternis.link failed', window.TernisLink.friendlyError(e));
  }
});

// Omnibox: `tl <long-url>` shortens without opening the popup.
chrome.omnibox.onInputEntered.addListener(async (text) => {
  const destination = text.trim();
  if (!/^https?:\/\//i.test(destination)) return;
  try {
    const { shortUrl } = await window.TernisLink.shorten(destination);
    await window.TernisLink.pushHistory({ shortUrl, destination, mode: 'omnibox' });
    await chrome.tabs.create({ url: shortUrl });
  } catch (e) {
    await notify('ternis.link failed', window.TernisLink.friendlyError(e));
  }
});
