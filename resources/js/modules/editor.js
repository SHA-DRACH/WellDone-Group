/**
 * Rich-text editing with Trix.
 *
 * Trix is loaded only when a <trix-editor> is actually on the page, which
 * keeps it out of the critical path for the public site.
 *
 * File attachments are disabled: images belong in the media library, where
 * they are validated, resized and tracked. Allowing arbitrary drag-and-drop
 * uploads into article bodies would bypass all of that.
 */
export async function initEditor() {
    if (! document.querySelector('trix-editor')) {
        return;
    }

    await import('trix');
    await import('trix/dist/trix.css');

    document.addEventListener('trix-file-accept', (event) => {
        event.preventDefault();
        window.alert('Please add images through the Media Library and insert the link, rather than dropping files into the editor.');
    });
}
