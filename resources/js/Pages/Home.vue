<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CommentForm from '@/Components/CommentForm.vue';

const props = defineProps({
    topics: Object,
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
                    <div class="mt-2 flex items-center gap-4" @click.stop>
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
