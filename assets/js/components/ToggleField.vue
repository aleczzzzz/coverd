<template>
    <div class="checkbox">
        <label>
            <input
                :checked="value"
                type="checkbox"
                @change="$emit('input', $event.target.checked)"
            > {{ label }}
        </label>
        <i
            v-if="helpText"
            v-tooltip
            :title="helpText"
            class="attribute-help-text fa fa-question-circle"
        ></i>
    </div>
</template>

<script>
    export default {
        name: 'BooleanField',
        props: {
            label: { type: String, required: true },
            value: { type: Boolean },
            helpText: { type: String, required: false, default: "" },
        }
    }
</script>