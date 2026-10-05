// register.js
export class FormValidator {
	constructor(config) {
		this.form = config.form;
		
		this.email = config.email || null;
		this.matric = config.matric || null;
		
		this.submitBtn = config.submitBtn;
		
		this.emailError = config.emailError || null;
		this.matricError = config.matricError || null;
		
		this.matricRegex = config.matricRegex || /^(202[0-6])\d{7}$/;
		this.emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		
		this.init();
	}
	
	init() {
		this.submitBtn.disabled = true;
		
		if (this.matric && this.email) this.matric.disabled = true;
		
		this.setupInput(this.email);
		this.setupInput(this.matric);
		
		this.setupCursor(this.submitBtn, this.submitBtn);
		
		this.form.addEventListener('submit', (e) => this.handleSubmit(e));
	}
	
	setupInput(input) {
		if (!input) return;
		
		const container = input.closest('div');
		
		input.addEventListener('focus', () => container.classList.add('focus'));
		input.addEventListener('blur', () => container.classList.remove('focus'));
		
		this.setupCursor(input, container);
		
		if (input === this.email) {
			input.addEventListener('input', () => this.validateEmail());
		} else if (input === this.matric) {
			input.addEventListener('input', () => this.validateMatric());
		}
		
		input.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') {
				e.preventDefault();
				
				if (input === this.email) {
					this.validateEmail();
					if (this.matric && !this.matric.disabled) {
						this.matric.focus();
					}
				} else if (input === this.matric) {
					this.validateMatric();
					if (!this.submitBtn.disabled) {
						this.form.requestSubmit();
					}
				}
			}
		});
	}
	
	setupCursor(input, container) {
		container.addEventListener('mouseenter', () => {
			if (input.disabled) {
				container.style.cursor = 'not-allowed';
			} else if (input.tagName.toLowerCase() === 'button') {
				container.style.cursor = 'pointer';
			} else {
				container.style.cursor = 'text';
			}
		});
		
		container.addEventListener('mouseleave', () => {
			container.style.cursor = '';
		});
	}
	
	// Email Validation
	validateEmail() {
		const container = this.email.closest('div');
		
		if (this.emailRegex.test(this.email.value.trim())) {
			if (this.emailError) this.emailError.textContent = '';
			this.setValid(container);
			
			if (this.matric) {
				this.matric.disabled = false;
			} else {
				this.submitBtn.disabled = false;
			}
		} else {
			if (this.emailError) this.emailError.textContent = 'Please enter a valid email.';
			this.setInvalid(container);
			
			if (this.matric) {
				this.matric.value = '';
				this.matric.disabled = true;
			}
			if (this.matric) {
				this.matric.value = '';
				this.matric.disabled = true;
			}
			
			this.submitBtn.disabled = true;
		}
	}
	
	// Matric No Validation
	validateMatric() {
		const container = this.matric.closest('div');
		
		if (this.email && !this.emailRegex.test(this.email.value.trim())) {
			this.validateEmail();
			this.email.focus();
			return;
		}
		
		if (this.matricRegex.test(this.matric.value.trim())) {
			if (this.matricError) this.matricError.textContent = '';
			this.setValid(container);
			
			this.submitBtn.style.display = "block";
			this.submitBtn.disabled = false;
		} else {
			if (this.matricError) this.matricError.textContent = 'Matric Number must be exactly 11 digits and begin with 2020–2026!';
			this.setInvalid(container);
			
			this.submitBtn.disabled = true;
		}
		
	}
	
	handleSubmit(e) {
		if (this.email && !this.emailRegex.test(this.email.value.trim())) {
			e.preventDefault();
			this.validateEmail();
			this.email.focus();
			return;
		}
		
		if (this.matric && !this.matricRegex.test(this.matric.value.trim())) {
			e.preventDefault();
			this.validateMatric();
			this.matric.focus();
			return;
		}
		
		alert('Form submitted successfully!');
		this.form.submit();
	}
	
	// Updating UI feedback
	setValid(container) {
		container.style.border = "2px solid blue";
	}
	
	setInvalid(container) {
		container.style.border = "2px solid red";
	}
	
}