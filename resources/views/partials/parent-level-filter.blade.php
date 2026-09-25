{{-- Keeps only organizations of the level right above the chosen level in the parent select. --}}
<script>
    (() => {
        const parentLevels = @json(collect($levels)->mapWithKeys(fn ($level) => [
            $level->value => collect($levels)->first(fn ($parentLevel) => $parentLevel->childLevel() === $level)?->value,
        ]));
        const levelSelect = document.getElementById('level');
        const parentSelect = document.getElementById('parent_id');

        const filterParents = () => {
            const parentLevel = parentLevels[levelSelect.value];

            parentSelect.querySelectorAll('option[data-level]').forEach((option) => {
                option.hidden = option.dataset.level !== parentLevel;
                option.disabled = option.hidden;
            });

            if (parentSelect.selectedOptions[0]?.disabled) {
                parentSelect.value = '';
            }
        };

        levelSelect.addEventListener('change', filterParents);
        filterParents();
    })();
</script>
