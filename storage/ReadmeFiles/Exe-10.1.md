## What is CSRF?

Cross-Site Request Forgery (CSRF) is a type of web security vulnerability where an attacker tricks a user into performing unwanted actions on a web application in which they are already authenticated. This happens because the browser automatically includes credentials like cookies or session tokens with every request.

For example, if a user is logged into a banking website and visits a malicious site, that site can send a hidden request to transfer money without the user’s consent. Since the request carries valid authentication cookies, the server may treat it as legitimate.

CSRF attacks exploit the trust between the browser and the server. They do not require stealing login credentials but instead misuse an existing authenticated session.

To prevent CSRF attacks, web frameworks like Laravel use CSRF tokens. These tokens ensure that every request is intentionally made by the user and originates from the legitimate application, protecting sensitive operations like form submissions.

---

## How token is verified?

CSRF token verification works by generating a unique, unpredictable token for each user session and embedding it within forms or requests. In Laravel, this is done using the `@csrf` directive, which inserts a hidden input field containing the token.

When the form is submitted, the token is sent along with the request. The server then compares this token with the one stored in the user’s session. If both tokens match, the request is considered valid and processed further.

If the token is missing, incorrect, or expired, the server rejects the request and typically returns an error (such as a 419 status in Laravel). This prevents unauthorized or forged requests from being executed.

This mechanism ensures that only requests originating from the trusted application, where the token was generated, are accepted. It effectively blocks malicious third-party sites from performing actions on behalf of the user without their knowledge.
