{{--
  | Tab-bound session guard.
  |
  | SESSION_EXPIRE_ON_CLOSE makes the cookie die with the browser, which is the
  | correct server-side control. It does not cover a single tab being closed
  | while the browser stays open, and browsers that restore the previous
  | session on launch can hand the cookie back anyway.
  |
  | sessionStorage is per tab and is cleared when that tab closes, so it is the
  | one thing that reliably distinguishes "the tab that signed in" from "a tab
  | opened later". On sign-in the server flashes bind_tab, and this script
  | records the token. Any later page load in a tab without that token ends the
  | session server-side and returns to the sign-in screen.
  |
  | Set CELESTE_TAB_BOUND_SESSION=false to disable and rely on the cookie
  | lifetime alone -- see config/celeste.php for the trade-off.
--}}
@auth
@if (config('celeste.tab_bound_session'))
<script>
(function () {
    'use strict';

    var KEY   = 'celeste.tab';
    var token = @json(session('browser_token'));
    var bind  = @json((bool) session('bind_tab'));

    if (!token) {
        return; // nothing to compare against
    }

    // Just signed in: this is the tab that owns the session.
    if (bind) {
        try { sessionStorage.setItem(KEY, token); } catch (e) {}
        return;
    }

    var held = null;
    try { held = sessionStorage.getItem(KEY); } catch (e) { return; }

    if (held === token) {
        return; // same tab, carry on
    }

    // A tab that did not sign in. End the session server-side rather than
    // merely redirecting, so the cookie cannot be reused by pressing Back.
    document.documentElement.style.visibility = 'hidden';

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = @json(route('logout'));

    var csrf = document.createElement('input');
    csrf.type  = 'hidden';
    csrf.name  = '_token';
    csrf.value = @json(csrf_token());
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
})();
</script>
@endif
@endauth