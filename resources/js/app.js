import Alpine from 'alpinejs';
import './echo';
import './notifications';
import './order-board';
import './shop';
import './dashboard';

const submitControlSelector = 'button:not([type]), button[type="submit"], input[type="submit"], input[type="image"]';

document.addEventListener('submit', (event) => {
	const form = event.target;

	if (!(form instanceof HTMLFormElement)) return;

	if (form.dataset.submitState) {
		event.preventDefault();
		return;
	}

	form.dataset.submitState = 'pending';

	queueMicrotask(() => {
		if (form.dataset.submitState !== 'pending') return;

		if (event.defaultPrevented) {
			delete form.dataset.submitState;
			return;
		}

		form.dataset.submitState = 'submitting';
		form.setAttribute('aria-busy', 'true');

		form.querySelectorAll(submitControlSelector).forEach((control) => {
			if (control.disabled) return;

			control.dataset.disabledBySubmit = 'true';
			control.disabled = true;
		});
	});
});

window.addEventListener('pageshow', () => {
	document.querySelectorAll('form[data-submit-state]').forEach((form) => {
		delete form.dataset.submitState;
		form.removeAttribute('aria-busy');

		form.querySelectorAll('[data-disabled-by-submit="true"]').forEach((control) => {
			control.disabled = false;
			delete control.dataset.disabledBySubmit;
		});
	});
});

window.Alpine = Alpine;
Alpine.start();
