<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import { Head, useForm } from "@inertiajs/vue3";

const props = defineProps({
    complaint: {
        type: Object,
        required: true,
    },
});

const statusStyles = {
    submitted: "bg-ocean-100 text-ocean-800",
    under_review: "bg-amber-100 text-amber-800",
    resolved: "bg-emerald-100 text-emerald-800",
    rejected: "bg-red-100 text-red-800",
};

const form = useForm({
    status: props.complaint.status === "submitted" ? "under_review" : props.complaint.status,
    resolution_notes: props.complaint.resolution_notes || "",
});

const submit = () => {
    form.patch(route("admin.complaints.update", props.complaint.id));
};
</script>

<template>
    <Head :title="complaint.subject" />

    <AppShell>
        <template #header>
            <h2 class="font-display font-semibold text-xl text-ocean-900 dark:text-white">
                Review Complaint
            </h2>
        </template>

        <div class="max-w-2xl mx-auto px-4 md:px-6 py-6">
            <h1 class="md:hidden font-display text-lg font-semibold text-ocean-900 dark:text-white mb-4">
                Review Complaint
            </h1>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6 mb-6">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h2 class="font-semibold text-lg text-ocean-900 dark:text-white">{{ complaint.subject }}</h2>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                        :class="statusStyles[complaint.status] || 'bg-ocean-100 text-ocean-800'"
                    >
                        {{ complaint.status_label }}
                    </span>
                </div>
                <p class="text-sm text-ocean-500 dark:text-neutral-400 mb-1">
                    {{ complaint.customer_name }} · {{ complaint.account_number }} · Submitted {{ complaint.created_at }}
                </p>
                <p v-if="complaint.phone || complaint.alternate_email" class="text-sm text-ocean-500 dark:text-neutral-400 mb-4">
                    <span v-if="complaint.phone">{{ complaint.phone }}</span>
                    <span v-if="complaint.phone && complaint.alternate_email"> · </span>
                    <span v-if="complaint.alternate_email">{{ complaint.alternate_email }}</span>
                </p>
                <p class="text-ocean-700 dark:text-neutral-300 mb-4">{{ complaint.description }}</p>
                <p v-if="complaint.bill_amount" class="text-sm text-ocean-700 dark:text-neutral-300 font-mono">
                    Related bill: KES {{ complaint.bill_amount }}
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-ocean-100 dark:border-white/5 shadow-soft p-6">
                <h3 class="font-semibold text-ocean-900 dark:text-white mb-4">Update Status</h3>
                <form @submit.prevent="submit">
                    <div class="space-y-2">
                        <label v-for="option in ['under_review', 'resolved', 'rejected']" :key="option" class="flex items-center gap-2">
                            <input type="radio" v-model="form.status" :value="option" class="text-ocean-600 focus:ring-ocean-500" />
                            <span class="text-sm text-ocean-800 capitalize">{{ option.replace('_', ' ') }}</span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.status" />

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-ocean-800 mb-1">Resolution Notes</label>
                        <textarea
                            v-model="form.resolution_notes"
                            rows="4"
                            maxlength="2000"
                            class="block w-full border-ocean-300 dark:border-white/10 dark:bg-neutral-800 dark:text-white focus:border-ocean-500 focus:ring-ocean-500 rounded-lg shadow-sm"
                            placeholder="Explain what was found and any adjustment made."
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.resolution_notes" />
                    </div>

                    <div v-if="$page.props.flash?.status" class="mt-4 text-sm text-ocean-700 dark:text-neutral-300 bg-ocean-50 rounded-md px-3 py-2">
                        {{ $page.props.flash.status }}
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Save Update
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppShell>
</template>
