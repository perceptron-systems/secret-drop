export default () => ({
    show: false,
    top: 0,
    left: 0,
    translateY: false,
    _scrollHandler: null,
    _clickHandler: null,

    open() {
        const rect = this.$refs.btn.getBoundingClientRect();
        const direction = this.$refs.btn.dataset.direction || 'above';
        const position = this.$refs.btn.dataset.position || 'start';
        const tooltipWidth = 288;
        const margin = 16;
        const maxLeft = window.innerWidth - tooltipWidth - margin;

        this.top = direction === 'below' ? rect.bottom + 8 : rect.top - 8;
        this.translateY = direction === 'above';

        const rawLeft = position === 'end' ? rect.right - tooltipWidth : rect.left;
        this.left = Math.max(margin, Math.min(rawLeft, maxLeft));

        this.show = true;
        this._addListeners();
    },

    close() {
        this.show = false;
        this._removeListeners();
    },

    _addListeners() {
        this._scrollHandler = () => this.close();
        this._clickHandler = (e) => {
            if (!this.$refs.btn.contains(e.target)) {
                this.close();
            }
        };
        window.addEventListener('scroll', this._scrollHandler, { passive: true, capture: true });
        document.addEventListener('click', this._clickHandler, { capture: true });
    },

    _removeListeners() {
        if (this._scrollHandler) {
            window.removeEventListener('scroll', this._scrollHandler, { capture: true });
            this._scrollHandler = null;
        }
        if (this._clickHandler) {
            document.removeEventListener('click', this._clickHandler, { capture: true });
            this._clickHandler = null;
        }
    },

    tooltipStyle() {
        const transform = this.translateY ? 'translateY(-100%)' : '';

        return `position:fixed;top:${this.top}px;left:${this.left}px;transform:${transform};z-index:9999`;
    },
});
