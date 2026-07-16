export function createVideoThumbnail(file) {
    return new Promise((resolve, reject) => {
        const video = document.createElement('video');
        const objectUrl = URL.createObjectURL(file);
        const timeout = window.setTimeout(() => finish(null, new Error('Pembuatan thumbnail terlalu lama.')), 15000);

        const finish = (thumbnail, error = null) => {
            window.clearTimeout(timeout);
            URL.revokeObjectURL(objectUrl);
            video.removeAttribute('src');
            video.load();
            if (error) reject(error);
            else resolve(thumbnail);
        };

        video.muted = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.onerror = () => finish(null, new Error('Video tidak dapat dibaca untuk membuat thumbnail.'));
        video.onloadedmetadata = () => {
            video.currentTime = Math.min(Math.max(video.duration * 0.1, 0.1), 1);
        };
        video.onseeked = () => {
            const width = Math.min(video.videoWidth, 1280);
            const height = Math.max(1, Math.round(video.videoHeight * (width / video.videoWidth)));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(video, 0, 0, width, height);
            canvas.toBlob((blob) => {
                if (!blob) {
                    finish(null, new Error('Browser gagal membuat thumbnail video.'));
                    return;
                }

                const extension = blob.type === 'image/webp' ? 'webp' : 'jpg';
                finish(new File([blob], `thumbnail.${extension}`, { type: blob.type }));
            }, 'image/webp', 0.82);
        };
        video.src = objectUrl;
    });
}
