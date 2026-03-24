export default () => ({
    isOpen: false,
    highlightedIndex: 0,

    openPalette() {
        this.isOpen = true;
        document.body.style.overflow = 'hidden';

        const items = this.getItems();
        const currentIndex = items.findIndex(item => item.getAttribute('aria-current') === 'true');
        this.highlightedIndex = currentIndex >= 0 ? currentIndex : 0;
        this.updateHighlight();

        setTimeout(() => {
            const items = this.getItems();
            const target = items[this.highlightedIndex];

            if (target) {
                target.focus({ preventScroll: true });
            }
        }, 100);
    },

    closePalette() {
        this.isOpen = false;
        document.body.style.overflow = '';
    },

    togglePalette() {
        if (this.isOpen) {
            this.closePalette();
        } else {
            this.openPalette();
        }
    },

    getItems() {
        return Array.from(this.$refs.results.querySelectorAll('[data-locale]'));
    },

    updateHighlight() {
        this.getItems().forEach((item, i) => {
            const isActive = i === this.highlightedIndex;
            item.classList.toggle('cmd-highlighted', isActive);
            item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    },

    highlightItem(el) {
        const items = this.getItems();
        const index = items.indexOf(el);

        if (index >= 0) {
            this.highlightedIndex = index;
            this.updateHighlight();
        }
    },

    handleGlobalKeydown(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            if (!this.isOpen) {
                const tag = event.target.tagName;

                if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                    return;
                }
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            this.togglePalette();
        }

    },

    handleKeydown(event) {
        const items = this.getItems();

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.highlightedIndex = Math.min(this.highlightedIndex + 1, items.length - 1);
            this.updateHighlight();
            items[this.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
            this.updateHighlight();
            items[this.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const item = items[this.highlightedIndex];

            if (item) {
                window.location.href = item.href;
            }
        }
    },
});
