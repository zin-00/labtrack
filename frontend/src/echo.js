import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'zfw9iorec1mrb9z6pzeg',
    wsHost:  '172.168.1.155',
    wsPort: 8080 ?? 80,
    wssPort: 8080 ?? 443,
    forceTLS: ('http' ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});