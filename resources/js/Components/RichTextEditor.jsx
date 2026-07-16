import { useEffect, useId } from 'react';

const TINYMCE_SRC = 'https://cdn.tiny.cloud/1/5resq762k4sk67ndud5u3evnk9px1iz6p2i7528fkus38ahz/tinymce/6/tinymce.min.js';

let tinyMceLoader;

function loadTinyMce() {
    if (window.tinymce) {
        return Promise.resolve(window.tinymce);
    }

    if (!tinyMceLoader) {
        tinyMceLoader = new Promise((resolve, reject) => {
            const existingScript = document.querySelector(`script[src="${TINYMCE_SRC}"]`);

            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(window.tinymce), { once: true });
                existingScript.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = TINYMCE_SRC;
            script.referrerPolicy = 'origin';
            script.onload = () => resolve(window.tinymce);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    return tinyMceLoader;
}

export default function RichTextEditor({ value, onChange, textareaClassName = '' }) {
    const reactId = useId();
    const editorId = `editor-${reactId.replace(/:/g, '')}`;

    useEffect(() => {
        let active = true;

        loadTinyMce().then((tinymce) => {
            if (!active) {
                return;
            }

            tinymce.init({
                selector: `#${editorId}`,
                height: 400,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'anchor',
                    'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount',
                ],
                toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | removeformat | help',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; line-height: 1.6; }',
                branding: false,
                promotion: false,
                setup: (editor) => {
                    editor.on('init', () => editor.setContent(value || ''));
                    editor.on('change keyup undo redo', () => onChange(editor.getContent()));
                },
            });
        });

        return () => {
            active = false;
            if (window.tinymce) {
                window.tinymce.remove(`#${editorId}`);
            }
        };
    }, [editorId]);

    return (
        <textarea
            id={editorId}
            defaultValue={value}
            className={textareaClassName}
        />
    );
}
