<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    images: {
        type: Array,
        required: true,
    },
});

const lightboxIndex = ref(null);

const openLightbox = (index) => {
    lightboxIndex.value = index;
};

const closeLightbox = () => {
    lightboxIndex.value = null;
};

const prev = () => {
    lightboxIndex.value = (lightboxIndex.value - 1 + props.images.length) % props.images.length;
};

const next = () => {
    lightboxIndex.value = (lightboxIndex.value + 1) % props.images.length;
};

const onKeydown = (e) => {
    if (lightboxIndex.value === null) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === 'ArrowRight') next();
};

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            v-for="(image, index) in images"
            :key="index"
            type="button"
            @click.stop="openLightbox(index)"
        >
            <img
                :src="image.url"
                class="h-24 w-24 rounded object-cover border border-gray-200 transition-opacity hover:opacity-80 cursor-zoom-in"
                alt=""
            />
        </button>
    </div>

    <!-- Лайтбокс -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="lightboxIndex !== null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/85"
                @click.self="closeLightbox"
            >
                <!-- Изображение -->
                <img
                    :src="images[lightboxIndex].url"
                    class="max-h-[90vh] max-w-[90vw] rounded object-contain shadow-2xl"
                    alt=""
                />

                <!-- Кнопка закрытия -->
                <button
                    type="button"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/25 transition-colors"
                    @click="closeLightbox"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>

                <!-- Стрелки (только если изображений больше одного) -->
                <template v-if="images.length > 1">
                    <button
                        type="button"
                        class="absolute left-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/25 transition-colors"
                        @click="prev"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                            <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="absolute right-4 top-1/2 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/25 transition-colors"
                        @click="next"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Счётчик -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-black/50 px-3 py-1 text-sm text-white">
                        {{ lightboxIndex + 1 }} / {{ images.length }}
                    </div>
                </template>
            </div>
        </Transition>
    </Teleport>
</template>
