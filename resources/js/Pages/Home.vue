<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CommentForm from '@/Components/CommentForm.vue';
import ImageGallery from '@/Components/ImageGallery.vue';

const props = defineProps({
    topics: Object,
    allTags: {
        type: Array,
        default: () => [],
    },
    currentTag: {
        type: String,
        default: null,
    },
});

const votesData = ref({});
const votingStates = ref({});

const getVote = (topic) => {
    if (votesData.value[topic.id] !== undefined) return votesData.value[topic.id];
    return { likes_count: topic.likes_count ?? 0, dislikes_count: topic.dislikes_count ?? 0, user_vote: topic.user_vote ?? null };
};

const sendVote = async (topicId, voteValue) => {
    if (votingStates.value[topicId]) return;
    votingStates.value[topicId] = true;

    try {
        const response = await fetch(route('comments.vote', topicId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ vote: voteValue }),
        });

        if (response.ok) {
            const data = await response.json();
            votesData.value[topicId] = data;
        }
    } finally {
        votingStates.value[topicId] = false;
    }
};

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
    <Head title="Пример LLM-генерации" />

    <div class="min-h-screen bg-gray-100">
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900">Пример LLM-генерации</h1>
            <p class="mt-2 text-sm text-gray-600">
                Данный мини-сайт в качестве <u>эксперимента</u> был полностью написан системой Claude Code за утро и вечер одного дня. Человек не написал тут ни одной строчки кода, не выполнил ни одной bash-команды.
                Стек: Laravel 12, Vue 3, Inertia.js, Filament 3, Tailwind CSS, MySQL, Docker.
                Исходный код:
                <a href="https://github.com/another-style/project" target="_blank" class="text-indigo-600 hover:text-indigo-500 underline">GitHub</a>.
                Обсуждение:
                <a href="https://another-it.ru/2026/02/18/llm-cant-write-working-code/" target="_blank" class="text-indigo-600 hover:text-indigo-500 underline">тут</a>.
            </p>

            <div class="mt-6 rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-medium text-gray-900">Создать новую тему</h2>
                <CommentForm placeholder="О чём хотите поговорить?" :show-tags="true" />
            </div>

            <!-- Облако тегов -->
            <div v-if="allTags.length > 0" class="mt-4 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">Теги:</span>
                    <Link
                        v-if="currentTag"
                        href="/"
                        class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700 hover:bg-gray-300"
                    >
                        Сбросить фильтр &times;
                    </Link>
                    <Link
                        v-for="tag in allTags"
                        :key="tag.name"
                        :href="'/?tag=' + encodeURIComponent(tag.name)"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="currentTag === tag.name
                            ? 'bg-indigo-600 text-white'
                            : 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200'"
                    >
                        {{ tag.name }}
                        <span class="opacity-60">{{ tag.count }}</span>
                    </Link>
                </div>
            </div>

            <div class="mt-8 space-y-4">
                <h2 class="text-lg font-medium text-gray-900">Темы</h2>

                <div v-if="topics.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow-sm">
                    Пока нет ни одной темы. Создайте первую!
                </div>

                <div
                    v-for="topic in topics.data"
                    :key="topic.id"
                    class="rounded-lg bg-white shadow-sm transition hover:shadow-md"
                >
                    <Link
                        :href="route('comments.show', topic.id)"
                        class="block p-4 pb-2"
                    >
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ topic.name || 'Аноним' }}</span>
                                <span v-if="topic.is_pinned" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                                        <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                                    </svg>
                                    Закреплено
                                </span>
                            </div>
                            <time>{{ formatDate(topic.created_at) }}</time>
                        </div>
                        <div class="markdown-content mt-2 text-sm text-gray-800 line-clamp-3" v-html="topic.message_html"></div>
                    </Link>

                    <!-- Изображения — вне <Link>, чтобы клик не вёл на страницу темы -->
                    <div v-if="topic.images && topic.images.length > 0" class="px-4 pb-2">
                        <ImageGallery :images="topic.images" />
                    </div>

                    <div class="px-4 pb-3">
                        <!-- Теги темы -->
                        <div v-if="topic.tags && topic.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                            <Link
                                v-for="tag in topic.tags"
                                :key="tag.id"
                                :href="'/?tag=' + encodeURIComponent(tag.name)"
                                class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 hover:bg-indigo-200"
                            >
                                {{ tag.name }}
                            </Link>
                        </div>
                        <div class="mt-2 flex items-center gap-4">
                            <button
                                @click.prevent="sendVote(topic.id, 1)"
                                class="flex items-center gap-1 text-sm transition-colors"
                                :class="getVote(topic).user_vote === 1 ? 'text-green-600' : 'text-gray-400 hover:text-green-600'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                    <path d="M1 8.998a1 1 0 0 1 1-1h3v9H2a1 1 0 0 1-1-1v-7Zm5.5 8.25 1.886-.943A11.985 11.985 0 0 0 12.5 11.06V3.5a1 1 0 0 0-1-1h-.33a1.75 1.75 0 0 0-1.634 1.127l-1.16 3.034A.25.25 0 0 1 8.142 6.8H6.5v10.448Z" />
                                </svg>
                                <span>{{ getVote(topic).likes_count }}</span>
                            </button>
                            <button
                                @click.prevent="sendVote(topic.id, -1)"
                                class="flex items-center gap-1 text-sm transition-colors"
                                :class="getVote(topic).user_vote === -1 ? 'text-red-600' : 'text-gray-400 hover:text-red-600'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                    <path d="M19 11.002a1 1 0 0 1-1 1h-3v-9h2a1 1 0 0 1 1 1v7Zm-5.5-8.25-1.886.943A11.985 11.985 0 0 0 7.5 8.94v7.56a1 1 0 0 0 1 1h.33a1.75 1.75 0 0 0 1.634-1.127l1.16-3.034a.25.25 0 0 1 .234-.139H13.5V2.752Z" />
                                </svg>
                                <span>{{ getVote(topic).dislikes_count }}</span>
                            </button>
                        </div>
                        <Link
                            v-if="topic.last_comment_at && topic.last_comment_at !== topic.created_at"
                            :href="topic.last_comment_link || (route('comments.show', topic.id) + '#comment-' + topic.last_comment_id)"
                            class="mt-1 block text-xs text-indigo-500 hover:text-indigo-700"
                        >
                            Последний ответ: {{ formatDate(topic.last_comment_at) }}
                        </Link>
                    </div>
                </div>
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
