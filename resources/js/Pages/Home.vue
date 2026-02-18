<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CommentForm from '@/Components/CommentForm.vue';

defineProps({
    topics: Object,
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Дискус" />

    <div class="min-h-screen bg-gray-100">
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900">Дискус</h1>

            <div class="mt-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-medium text-gray-900">Создать новую тему</h2>
                <CommentForm placeholder="О чём хотите поговорить?" />
            </div>

            <div class="mt-8 space-y-4">
                <h2 class="text-lg font-medium text-gray-900">Темы</h2>

                <div v-if="topics.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow-sm">
                    Пока нет ни одной темы. Создайте первую!
                </div>

                <Link
                    v-for="topic in topics.data"
                    :key="topic.id"
                    :href="route('comments.show', topic.id)"
                    class="block rounded-lg bg-white p-4 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span class="font-medium text-gray-900">{{ topic.name || 'Аноним' }}</span>
                        <time>{{ formatDate(topic.created_at) }}</time>
                    </div>
                    <p class="mt-2 text-sm text-gray-800 line-clamp-3">{{ topic.message }}</p>
                    <Link
                        v-if="topic.last_comment_at && topic.last_comment_at !== topic.created_at"
                        :href="route('comments.show', topic.id) + '#comment-' + topic.last_comment_id"
                        class="mt-1 block text-xs text-indigo-500 hover:text-indigo-700"
                        @click.stop
                    >
                        Последний ответ: {{ formatDate(topic.last_comment_at) }}
                    </Link>
                </Link>
            </div>

            <div v-if="topics.last_page > 1" class="mt-6 flex justify-center gap-2">
                <template v-for="link in topics.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded-md px-3 py-2 text-sm"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'bg-white text-gray-700 hover:bg-gray-50': !link.active,
                        }"
                    />
                    <span
                        v-else
                        v-html="link.label"
                        class="rounded-md px-3 py-2 text-sm text-gray-400 cursor-default"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
