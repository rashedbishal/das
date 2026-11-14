export type ImageRules = {
    targetSizeKb: number;
    requiredDimension: number;
};

export function imageDimensions(
    file: File,
): Promise<{ width: number; height: number }> {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            resolve({ width: img.naturalWidth, height: img.naturalHeight });
            URL.revokeObjectURL(url);
        };
        img.onerror = () => {
            reject(new Error('Could not read the image.'));
            URL.revokeObjectURL(url);
        };
        img.src = url;
    });
}

function count(n: number, singular: string, plural: string): string {
    return n === 1 ? `1 ${singular}` : `${n} ${plural}`;
}

/**
 * Warnings for images the server will re-process: files over the target size
 * get compressed, and files that aren't the required square get resized.
 * Neither blocks the upload.
 */
export async function imageWarnings(
    files: File[],
    rules: ImageRules,
): Promise<string[]> {
    const size = rules.requiredDimension;
    let oversized = 0;
    let wrongSize = 0;

    for (const file of files) {
        if (file.size > rules.targetSizeKb * 1024) {
            oversized++;
        }

        try {
            const { width, height } = await imageDimensions(file);
            if (width !== size || height !== size) {
                wrongSize++;
            }
        } catch {
            // Unreadable files are reported by the server.
        }
    }

    const warnings: string[] = [];

    if (wrongSize > 0) {
        warnings.push(
            `${count(wrongSize, 'image is', 'images are')} not ${size}×${size}px and will be resized to fit. Quality may drop and the image may look stretched.`,
        );
    }

    if (oversized > 0) {
        warnings.push(
            `${count(oversized, 'image is', 'images are')} larger than ${rules.targetSizeKb} KB and will be compressed by the server. Image quality may not hold.`,
        );
    }

    return warnings;
}
