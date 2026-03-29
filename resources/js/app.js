import './bootstrap';
import './console-banner';

import Alpine from '@alpinejs/csp';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

import * as SecretCrypto from './crypto';
import secretForm from './components/secret-form.js';
import secretViewer from './components/secret-viewer.js';
import themeToggle from './components/theme-toggle.js';
import languageSwitcher from './components/language-switcher.js';
import adminSecrets from './admin-secrets.js';
import footerMenu from './components/footer-menu.js';

Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.data('secretForm', secretForm);
Alpine.data('secretViewer', secretViewer);
Alpine.data('themeToggle', themeToggle);
Alpine.data('languageSwitcher', languageSwitcher);
Alpine.data('adminSecrets', adminSecrets);
Alpine.data('footerMenu', footerMenu);

window.Alpine = Alpine;
window.SecretCrypto = SecretCrypto;

Alpine.start();
