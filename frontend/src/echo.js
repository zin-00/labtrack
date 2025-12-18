import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'zfw9iorec1mrb9z6pzeg',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: ('http' ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});