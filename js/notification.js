$(document).on("click.bs.dropdown", "#dropNotifications", function (e) {
    if (e.target.tagName !== 'A') {
        e.stopPropagation();
        e.preventDefault();
    }
});

$(document).on("shown.bs.dropdown", "#testdropd", function () {
    const dropdown = $('#dropNotifications');

    dropdown.empty();
    dropdown.append('<li class="list-group-item text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</li>');

    $.ajax({
        type: 'GET',
        url: $(this).data('url'),
        success: function (data) {
            makeNotifications(data);
        },
        error: function () {
            dropdown.empty();
            dropdown.append('<li class="list-group-item text-center">Ocurrió un error al intentar comunicarse con el servidor.<br>Reintente en un momento o póngase en contacto con el administrador.</li>');
        }
    });
});

$(document).on("hidden.bs.dropdown", "#testdropd", function (e) {
    $('#dropNotifications').empty();
});

$(document).on("click", ".see-notif", function (e) {
    e.stopPropagation();
    e.preventDefault();

    let id = $(this).data('id');

    $.ajax({
        type: 'GET',
        url: urlSeeNotification,
        data: {"id": id},
        success: function (data) {
            let selectorId = $('#nid' + id);

            let mark;
            let icon;

            if (data.seen === 0) {
                selectorId.addClass('notification-item-unread');
                icon = 'check-lg';
                mark = 'read';
            } else {
                selectorId.removeClass('notification-item-unread');
                icon = 'check2-all';
                mark = 'unread';
            }

            const html = makeItemNotification(id, data.content, data.date, icon, mark, data.type);

            selectorId.html(html);

            if (data.count > 0) {
                $('.mark-all-read').show();
            } else {
                $('.mark-all-read').hide();
            }

            updateCount(data.count);
            updateCountUnread();
        },
        error: function (jqXHR, textStatus) {
            nerror(textStatus);
        }
    });

    return true;
});

$(document).on("click", ".del-notif", function (e) {
    e.stopPropagation();
    e.preventDefault();

    let id = $(this).data('id');

    $.ajax({
        type: 'POST',
        url: urlDelNotification + '?id=' + id,
        success: function (data) {
            $('#nid' + id).remove();

            updateCount(data.count);
            updateCountUnread();
            updateFooterNotification();
        },
        error: function (jqXHR, textStatus) {
            nerror(textStatus);
        }
    });

    return true;
});

$(document).on("click", ".del-all-notif", function (e) {
    e.stopPropagation();
    e.preventDefault();

    $.ajax({
        type: 'POST',
        url: urlDelAllNotification,
        success: function (data) {
            $.each($('.notification-item'), function (index, value) {
                if (!$(value).hasClass('notification-item-unread')) {
                    $(value).remove();
                }
            });

            updateCount(data.count);
            $('.del-all-notif').hide();
            updateFooterNotification();
        },
        error: function (jqXHR, textStatus) {
            nerror(textStatus);
        }
    });

    return true;
});

$(document).on("click", ".mark-all-read", function (e) {
    e.stopPropagation();
    e.preventDefault();

    $.ajax({
        type: 'POST',
        url: urlMarkAllRedNotification,
        success: function (data) {
            $.each($('.notification-item'), function (index, value) {
                $(value).removeClass('notification-item-unread');
            });
            $.each($('.see-notif'), function (index, value) {
                $(value).html('<i class="bi-check2-all"></i> Mark as unread');
            });

            $('.mark-all-read').hide();

            updateCount(data.count);
            updateCountUnread();
        },
        error: function (jqXHR, textStatus) {
            nerror(textStatus);
        }
    });

    return true;
});

function makeNotifications(data) {
    const dropdown = $('#dropNotifications');

    dropdown.empty();

    let html = '<li id="notif-header" class="list-group-item text-center"></li>';

    if (data.notificaciones.length > 0) {
        html += '<div style="max-height: 500px; overflow-y: auto;">';

        data.notificaciones.forEach(function (notification) {
            let _class = '';
            let icon = 'check2-all';
            let mark = 'unread';

            if (notification.seen === 0) {
                _class = 'notification-item-unread';
                icon = 'check-lg';
                mark = 'read';
            }

            html += '<li id="nid' + notification.id + '" class="list-group-item notification-item ' + _class + '">';
            html += makeItemNotification(notification.id, notification.content, notification.date, icon, mark, notification.type);
            html += '</li>';
        });

        html += '</div>';
    }

    html += '<li id="notif-footer" class="list-group-item d-flex justify-content-end align-items-center" style="padding: 0.5rem 0.5rem; min-height: 3rem;">';
    html += '<button class="btn btn-secondary btn-sm mark-all-read"><i class="bi bi-check2-all"></i> Mark all as read</button>';
    html += '<button class="ml-2 btn btn-danger btn-sm del-all-notif"><i class="bi bi-trash-fill"></i> Delete all</button>';
    html += '</li>';

    dropdown.append(html);

    if (data.count > 0) {
        $('.mark-all-read').show();
    } else {
        $('.mark-all-read').hide();
    }

    updateCount(data.count);
    updateCountUnread();
    updateFooterNotification();
}

function makeItemNotification(id, content, date, icon, mark, type) {
    let html = '<div class="media py-2">';
    html += '<i class="' + type.icon + ' display-5 mr-1 ' + type.color + '"></i>';
    html += '<div class="media-body px-2">';
    html += '<p class="mb-2 text-justify text-break" style="line-height: 1.1;">' + content + '</p>';
    html += '<div class="d-flex justify-content-between align-items-center">';
    html += '<small class="text-muted font-weight-light"><i class="bi bi-clock"></i> ' + date + '</small>';
    html += '<div>';
    html += '<button class="btn btn-light btn-sm see-notif" data-id="' + id + '"><i class="bi bi-' + icon + '"></i> Mark as ' + mark + '</button>';
    html += '&nbsp';
    html += '<button class="btn btn-light btn-sm del-notif" data-id="' + id + '"><i class="bi bi-trash-fill"></i></button>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    return html;
}

function updateCount(count) {
    let selectorNotificationUnread = $('.notification-unread');

    if (count === 0) {
        $('#notif-header').html('No have new notifications');
        selectorNotificationUnread.hide();
    } else {
        $('#notif-header').html('You have ' + count + ' notifications unread');
        selectorNotificationUnread.show();
    }
    selectorNotificationUnread.text(count);
}

function updateCountUnread() {
    let countRead = 0;

    $.each($('.notification-item'), function (index, value) {
        if (!$(value).hasClass('notification-item-unread')) {
            countRead++;
        }
    });

    if (countRead === 0) {
        $('.del-all-notif').hide();
    } else {
        $('.del-all-notif').show();
    }
}

function updateFooterNotification() {
    const divElement = $('#notif-footer');

    if ($('.notification-item').length === 0) {
        divElement.addClass('d-none');
        divElement.removeClass('d-flex');
    } else {
        divElement.addClass('d-flex');
        divElement.removeClass('d-none');
    }
}
