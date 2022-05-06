const client = new tmi.Client({
	options: { debug: true },
	identity: {
		username: channel,
		password: key
	},
	channels: [ channel ]
});

console.log(channel, client);

client.connect();

client.on('message', (channel, tags, message, self) => {
	// Ignore echoed messages.
	//if(self) return;

	if(message.toLowerCase() === '!bh' || message.toLowerCase() === '!betterhalf') {
        let Url = "https://half.go2boss.com/scripts/php/responder.php";
        let searchParams = new URLSearchParams();
        
        searchParams.append('racer', tags.username);

        let options = {
            method: 'POST',
            body: searchParams
        };

        fetch(Url, options)
        .then(res => {return res.json()})
        .then(data => {client.say(channel, `@${tags.username}, ${data.result}`)})
        .catch(error => console.log(error))
	}
});