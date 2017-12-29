(function($, undefined) {
	var VarnishPurge, PurgeBan,

	VarnishPurge = function() {
		this.init();
	}

	VarnishPurge.prototype.init = function() {
		$('form.purgeban').each(function() {
			new PurgeBan(this, $('.purgeban-output'));
		});
	}

	PurgeBan = function(form, $output) {
		this.$form = $(form);
		this.$output = $output;
		this.$form.submit($.proxy(this.submit, this));
	}

	PurgeBan.prototype.submit = function(event) {
		event.preventDefault();

		$.post(this.$form.attr('action'), this.$form.serialize())
			.then($.proxy(function(response) {
				this.$output.html(response.message);
			}, this));
	}

	new VarnishPurge();
}(window.jQuery));