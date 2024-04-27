import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import dialog from '@fylgja/alpinejs-dialog';

Alpine.plugin(dialog)
Livewire.start()
