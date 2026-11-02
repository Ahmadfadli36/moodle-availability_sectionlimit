/**
 * JavaScript for form editing section limit conditions.
 *
 * @module moodle-availability_sectionlimit-form
 */
M.availability_sectionlimit = M.availability_sectionlimit || {};

/**
 * @class M.availability_sectionlimit.form
 * @extends M.core_availability.plugin
 */
M.availability_sectionlimit.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin (nothing to do: the condition has no server-side options).
 *
 * @method initInner
 */
M.availability_sectionlimit.form.initInner = function() {
    // No initialisation needed.
};

M.availability_sectionlimit.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<span class="col-form-label pe-3">' +
            M.util.get_string('title', 'availability_sectionlimit') + '</span>' +
            '<span class="availability-sectionlimit">' +
            '<label><span class="visually-hidden accesshide">' +
            M.util.get_string('label_max', 'availability_sectionlimit') + '</span>' +
            '<input type="number" min="1" step="1" name="max" class="form-control" style="width: 6em" />' +
            '</label></span>';
    var node = Y.Node.create('<span class="d-flex flex-wrap align-items-center">' + html + '</span>');

    // Set initial value (default 4 for a new condition).
    var input = node.one('input[name=max]');
    if (json.max !== undefined) {
        input.set('value', json.max);
    } else {
        input.set('value', 4);
    }

    // Add event handlers (first time only).
    if (!M.availability_sectionlimit.form.addedEvents) {
        M.availability_sectionlimit.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('valuechange', function() {
            // The edit form must be updated whenever the number changes.
            M.core_availability.form.update();
        }, '.availability_sectionlimit input[name=max]');
    }

    return node;
};

M.availability_sectionlimit.form.fillValue = function(value, node) {
    value.max = parseInt(node.one('input[name=max]').get('value'), 10);
};

M.availability_sectionlimit.form.fillErrors = function(errors, node) {
    var value = parseInt(node.one('input[name=max]').get('value'), 10);
    if (isNaN(value) || value < 1) {
        errors.push('availability_sectionlimit:error_max');
    }
};
