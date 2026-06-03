import './bootstrap';

import Alpine from 'alpinejs';
import { registerAdminAlpine } from './admin-alpine';
import { initHouseForms } from './house-forms';
import { initAdminScroll } from './admin-scroll';

window.Alpine = Alpine;

registerAdminAlpine(Alpine);

Alpine.start();

initHouseForms();
initAdminScroll();
