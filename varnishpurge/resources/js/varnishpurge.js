(function($, undefined) {
	var VarnishPurge, PurgeBan,

	VarnishPurge = function() {
		this.init();
	}

	VarnishPurge.prototype.init = function() {
		$('form.purgeban').each(function() {
			new PurgeBan(this, $('#purgeban-output .output'));
		});
	}

	PurgeBan = function(form, $output) {
		this.$form = $(form);
		this.$output = $output;
		this.$form.submit($.proxy(this.submit, this));
	}

	PurgeBan.prototype.submit = function(event) {
		event.preventDefault();

		this.$output.html('');

		$.post(this.$form.attr('action'), this.$form.serialize())
			.then($.proxy(function(response) {
				// Update output
				this.$output.html(
					response.query + '\n\n' +
					response.message
				);

				// Update CSRF token
				this.$form.find('input[name=\'' + response.CSRF.name + '\']')
					.val(response.CSRF.value);
			}, this));
	}

	new VarnishPurge();
}(window.jQuery));