<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import CommentForm from '@/Components/CommentForm.vue';
import ImageGallery from '@/Components/ImageGallery.vue';

const props = defineProps({
    comment: {
        type: Object,
        required: true,
    },
});

const showReplyForm = ref(false);
const initialMessage = ref('');
const likesCount = ref(props.comment.likes_count ?? 0);
const dislikesCount = ref(props.comment.dislikes_count ?? 0);
const userVote = ref(props.comment.user_vote ?? null);
const voting = ref(false);
const messageEl = ref(null);
const commentFormRef = ref(null);

const toggleReplyForm = () => {
    if (showReplyForm.value) {
        showReplyForm.value = false;
        initialMessage.value = '';
        return;
    }

    // Проверяем выделенный текст внутри блока сообщения
    let quotedText = '';
    const selection = window.getSelection();
    if (selection && selection.toString().trim() && messageEl.value) {
        if (messageEl.value.contains(selection.anchorNode)) {
            quotedText = selection.toString().trim();
        }
    }

    if (quotedText) {
        const quoted = quotedText.split('\n').map(line => '> ' + line).join('\n');
        initialMessage.value = quoted + '\n\n';
    } else {
        initialMessage.value = '';
    }

    showReplyForm.value = true;
};

const addReference = () => {
    if (!showReplyForm.value) {
        initialMessage.value = `>>${props.comment.id}\n\n`;
        showReplyForm.value = true;
    } else {
        commentFormRef.value?.insertReference(props.comment.id);
    }
};

const sendVote = async (voteValue) => {
    if (voting.value) return;
    voting.value = true;

    try {
        const response = await fetch(route('comments.vote', props.comment.id), {
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
            likesCount.value = data.likes_count;
            dislikesCount.value = data.dislikes_count;
            userVote.value = data.user_vote;
        }
    } finally {
        voting.value = false;
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
    <div :id="'comment-' + comment.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between text-sm text-gray-500">
            <span class="font-medium text-gray-900">
                {{ comment.name || 'Аноним' }}
            </span>
            <div class="flex items-center gap-3">
                <time>{{ formatDate(comment.created_at) }}</time>
                <Link
                    :href="route('comments.show', comment.id)"
                    class="text-indigo-600 hover:text-indigo-800"
                >
                    #{{ comment.id }}
                </Link>
            </div>
        </div>

        <div v-if="comment.images && comment.images.length > 0" class="mt-3">
            <ImageGallery :images="comment.images" />
        </div>

        <div ref="messageEl" class="markdown-content mt-4 text-base text-gray-800" v-html="comment.message_html"></div>

        <div class="mt-3 flex items-center gap-4">
            <button
                @click="sendVote(1)"
                :disabled="voting"
                class="flex items-center gap-1 text-sm transition-colors"
                :class="userVote === 1 ? 'text-green-600' : 'text-gray-400 hover:text-green-600'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path d="M1 8.998a1 1 0 0 1 1-1h3v9H2a1 1 0 0 1-1-1v-7Zm5.5 8.25 1.886-.943A11.985 11.985 0 0 0 12.5 11.06V3.5a1 1 0 0 0-1-1h-.33a1.75 1.75 0 0 0-1.634 1.127l-1.16 3.034A.25.25 0 0 1 8.142 6.8H6.5v10.448Z" />
                </svg>
                <span>{{ likesCount }}</span>
            </button>

            <button
                @click="sendVote(-1)"
                :disabled="voting"
                class="flex items-center gap-1 text-sm transition-colors"
                :class="userVote === -1 ? 'text-red-600' : 'text-gray-400 hover:text-red-600'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path d="M19 11.002a1 1 0 0 1-1 1h-3v-9h2a1 1 0 0 1 1 1v7Zm-5.5-8.25-1.886.943A11.985 11.985 0 0 0 7.5 8.94v7.56a1 1 0 0 0 1 1h.33a1.75 1.75 0 0 0 1.634-1.127l1.16-3.034a.25.25 0 0 1 .234-.139H13.5V2.752Z" />
                </svg>
                <span>{{ dislikesCount }}</span>
            </button>

            <button
                @click="toggleReplyForm"
                class="text-sm text-indigo-600 hover:text-indigo-800"
            >
                {{ showReplyForm ? 'Отмена' : 'Ответить' }}
            </button>
        </div>

        <div class="mt-3 flex items-center gap-4">
            <button
                @click="addReference"
                class="text-xs text-indigo-500 hover:text-indigo-700"
            >
                Сослаться на этот комментарий
            </button>
            <span v-if="!showReplyForm" class="text-xs text-gray-400">Выделите текст и нажмите «Ответить», чтобы процитировать</span>
        </div>

        <div v-if="showReplyForm" class="mt-3">
            <CommentForm
                ref="commentFormRef"
                :parent-id="comment.id"
                :initial-message="initialMessage"
                placeholder="Написать ответ..."
                @submitted="showReplyForm = false"
            />
        </div>
    </div>
</template>
