import { ref, onMounted, onUnmounted, unref } from 'vue';

/**
 * useBarcodeScanner
 * 
 * Global hardware barcode scanner detector (USB / Bluetooth / 2.4GHz guns).
 * Hardware scanners behave like HID keyboards, dumping characters rapidly (< 50ms)
 * followed by an Enter or Tab key.
 * 
 * @param {Object} options
 * @param {Function} options.onScan - Callback with (barcode: string)
 * @param {number} [options.minLength=3] - Minimum barcode string length
 * @param {number} [options.maxInterval=50] - Max milliseconds between characters from scanner
 * @param {import('vue').Ref<boolean>|boolean} [options.enabled=true] - Toggle listening
 */
export function useBarcodeScanner(options = {}) {
    const {
        onScan,
        minLength = 3,
        maxInterval = 50,
        enabled = true,
    } = options;

    const buffer = ref('');
    const lastKeyTime = ref(0);
    const lastScannedCode = ref('');
    const isScanning = ref(false);

    function handleKeydown(e) {
        if (!unref(enabled)) return;

        // Ignore modifier key presses
        if (['Shift', 'Control', 'Alt', 'Meta', 'CapsLock', 'Tab'].includes(e.key)) {
            return;
        }

        const now = Date.now();
        const delta = now - lastKeyTime.value;
        lastKeyTime.value = now;

        if (e.key === 'Enter') {
            const scannedText = buffer.value.trim();
            // If buffer is long enough and timing indicates rapid scanner input
            if (scannedText.length >= minLength) {
                // If the user was typing slowly by hand (e.g. paused > 200ms before hitting Enter),
                // but the last key had a long gap, do not treat as scanner unless whole string was fast
                e.preventDefault();
                e.stopPropagation();

                lastScannedCode.value = scannedText;
                buffer.value = '';
                isScanning.value = false;

                if (typeof onScan === 'function') {
                    onScan(scannedText);
                }
                return;
            }
            buffer.value = '';
            isScanning.value = false;
            return;
        }

        // Only buffer printable single characters
        if (e.key && e.key.length === 1) {
            if (delta > maxInterval) {
                // Too slow: reset buffer and start fresh with current character
                buffer.value = e.key;
            } else {
                buffer.value += e.key;
            }
            isScanning.value = buffer.value.length >= 2;
        }
    }

    onMounted(() => {
        window.addEventListener('keydown', handleKeydown, true);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeydown, true);
    });

    return {
        buffer,
        lastScannedCode,
        isScanning,
    };
}

