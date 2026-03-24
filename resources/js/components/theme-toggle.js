export default () => ({
    dark: localStorage.getItem('theme') !== 'light',

    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    },

    ariaLabel() {
        return this.dark
            ? this.$el.dataset.labelLight
            : this.$el.dataset.labelDark;
    }
});
