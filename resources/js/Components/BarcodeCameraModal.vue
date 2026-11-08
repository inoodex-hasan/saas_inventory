<script setup>
import { ref, watch, onBeforeUnmount, nextTick } from 'vue';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import { playScanSuccess, playScanError } from '@/Utils/sound';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Scan Barcode with Camera',
    },
});

const emit = defineEmits(['update:open', 'scan']);

const scannerContainerId = 'pos-camera-barcode-reader';
const isRunning = ref(false);
const errorMessage = ref('');
const cameras = ref([]);
const currentCameraIndex = ref(0);
const torchEnabled = ref(false);
const hasTorch = ref(false);

let html5QrCode = null;

const supportedFormats = [
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.UPC_A,
    Html5QrcodeSupportedFormats.UPC_E,
    Html5QrcodeSupportedFormats.QR_CODE,
    Html5QrcodeSupportedFormats.DATA_MATRIX,
];

async function startScanner() {
    errorMessage.value = '';
    await nextTick();

    const elem = document.getElementById(scannerContainerId);
    if (!elem) return;

    try {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(scannerContainerId, {
                formatsToSupport: supportedFormats,
                verbose: false,
            });
        }

        // Get available cameras
        try {
            const devices = await Html5Qrcode.getCameras();
            if (devices && devices.length) {
                cameras.value = devices;
            }
        } catch (e) {
            cameras.value = [];
        }

        const cameraIdOrConfig = cameras.value.length > 0
            ? cameras.value[currentCameraIndex.value].id
            : { facingMode: 'environment' };

        const config = {
            fps: 15,
            qrbox: { width: 280, height: 180 },
            aspectRatio: 1.333,
        };

        await html5QrCode.start(
            cameraIdOrConfig,
            config,
            (decodedText) => {
                onScanSuccess(decodedText);
            },
            () => {
                // Ignore frame-by-frame non-matches
            }
        );

        isRunning.value = true;

        // Check torch capability
        try {
            const track = html5QrCode.getRunningTrackCameraCapabilities();
            hasTorch.value = !!track?.torchFeature?.()?.isSupported?.();
        } catch (e) {
            hasTorch.value = false;
        }
    } catch (err) {
        console.error('Camera Scanner start error:', err);
        errorMessage.value = err?.message || 'Could not access camera. Please allow camera permissions in your browser.';
        isRunning.value = false;
    }
}

async function stopScanner() {
    if (html5QrCode && isRunning.value) {
        try {
            await html5QrCode.stop();
        } catch (e) {
            // Ignore stop errors
        }
        isRunning.value = false;
    }
}

let scanCooldown = false;
function onScanSuccess(decodedText) {
    if (scanCooldown) return;
    scanCooldown = true;

    playScanSuccess();
    emit('scan', decodedText.trim());

    // Close after short delay for user feedback
    setTimeout(() => {
        closeModal();
        scanCooldown = false;
    }, 350);
}

function closeModal() {
    stopScanner();
    emit('update:open', false);
}

async function switchCamera() {
    if (cameras.value.length <= 1) return;
    await stopScanner();
    currentCameraIndex.value = (currentCameraIndex.value + 1) % cameras.value.length;
    await startScanner();
}

async function toggleTorch() {
    if (!html5QrCode || !hasTorch.value) return;
    try {
        torchEnabled.value = !torchEnabled.value;
        await html5QrCode.applyVideoConstraints({
            advanced: [{ torch: torchEnabled.value }],
        });
    } catch (e) {
        torchEnabled.value = false;
    }
}

watch(() => props.open, (newVal) => {
    if (newVal) {
        startScanner();
    } else {
        stopScanner();
    }
});

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
        <div class="relative w-full max-w-md bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800 bg-slate-900/90">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white tracking-wide">{{ title }}</h3>
                        <p class="text-[11px] text-slate-400">Aim camera at product barcode / QR code</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Torch Toggle -->
                    <button
                        v-if="hasTorch"
                        type="button"
                        @click="toggleTorch"
                        :class="torchEnabled ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:text-white'"
                        class="p-2 rounded-lg transition-colors text-xs font-semibold flex items-center gap-1"
                        title="Toggle Flash / Torch"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Camera Switcher -->
                    <button
                        v-if="cameras.length > 1"
                        type="button"
                        @click="switchCamera"
                        class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white transition-colors"
                        title="Switch Camera"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>

                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="closeModal"
                        class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Viewfinder Area -->
            <div class="relative bg-black flex items-center justify-center min-h-[300px] overflow-hidden">
                <div :id="scannerContainerId" class="w-full h-full"></div>

                <!-- Laser scanning beam animation -->
                <div v-if="isRunning" class="pointer-events-none absolute inset-x-8 top-1/4 bottom-1/4 border-2 border-indigo-500/70 rounded-xl overflow-hidden shadow-[0_0_15px_rgba(99,102,241,0.5)]">
                    <div class="w-full h-0.5 bg-rose-500 shadow-[0_0_12px_#ef4444] animate-scanner-beam"></div>
                    <div class="absolute top-2 left-2 text-[10px] uppercase font-bold tracking-wider text-indigo-300/80 bg-slate-900/80 px-2 py-0.5 rounded">
                        Target Barcode
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="errorMessage" class="absolute inset-x-6 p-4 rounded-xl bg-rose-950/90 border border-rose-500/40 text-center">
                    <svg class="w-8 h-8 text-rose-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-xs font-semibold text-rose-200">{{ errorMessage }}</p>
                    <button
                        type="button"
                        @click="startScanner"
                        class="mt-3 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition-colors"
                    >
                        Retry Access
                    </button>
                </div>
            </div>

            <!-- Footer Tips -->
            <div class="px-5 py-3 bg-slate-900/90 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :class="isRunning ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                    {{ isRunning ? 'Camera Active' : 'Initializing camera...' }}
                </span>
                <span class="text-slate-500">Supports UPC, EAN, Code-128 & QR</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scannerBeam {
    0% { transform: translateY(0); }
    50% { transform: translateY(120px); }
    100% { transform: translateY(0); }
}

.animate-scanner-beam {
    animation: scannerBeam 2s ease-in-out infinite;
}
</style>

