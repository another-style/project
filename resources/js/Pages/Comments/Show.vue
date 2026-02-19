<script setup>
import { onMounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CommentItem from '@/Components/CommentItem.vue';
import CommentTree from '@/Components/CommentTree.vue';

defineProps({
    comment: Object,
    children: {
        type: Array,
        default: () => [],
    },
});

onMounted(() => {
    const hash = window.location.hash;
    if (hash) {
        nextTick(() => {
            const el = document.querySelector(hash);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
});
</script>

<template>
    <Head :title="`Комментарий #${comment.id}`" />

    <div class="min-h-screen bg-gray-100">
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-4">
                <Link
                    v-if="comment.parent_id"
                    :href="route('comments.show', comment.parent_id)"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    &larr; К родительскому комментарию
                </Link>
                <Link
                    :href="route('home')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    &larr; На главную
                </Link>
            </div>

            <CommentItem :comment="comment" />

            <div v-if="children.length > 0" class="mt-4 ml-2 border-l-2 border-gray-200 pl-2 sm:ml-6 sm:pl-4">
                <CommentTree :comments="children" />
            </div>

            <div v-else class="mt-4 rounded-lg bg-white p-4 text-center text-sm text-gray-500">
                Пока нет ответов.
            </div>
        </div>
    </div>
</template>
