<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <button class="btn btn-success" @click="joinBroadcast">Join Stream</button><br>
                <video autoplay ref="viewer"></video>
            </div>
        </div>
    </div>
</template>

<script>
    import Peer from 'simple-peer';
    export default {
        name: 'viewer',
        props: [
            'auth_user_id', 'stream_id', 'turn_url', 'turn_credential'
        ],
        data(){
            return {
                streamPresenceChannel: null,
                broadcasterPeer: null,
                broadcasterId: null,
            }
        },
        methods: {
            joinBroadcast(){
                this.initializeStreamingChannel();
                this.initializeSignalOfferChannel();
            },
            initializeStreamingChannel(){
                this.streamPresenceChannel = Echo.join(`stream-channel.${this.stream_id}`)
                console.log('joining streaming', this.stream_id)
            },
            createViewerPeer(incomingOffer, broadcaster) {
                const peer = new Peer({
                    initiator: false,
                    trickle: false,
                    // config: {
                    //     iceServices: [
                    //         {
                    //             urls: 'stun:stun.stunprotocol.org',
                    //         },
                    //         {
                    //             urls: this.turn_url,
                    //             username: this.turn_username,
                    //             credential: this.turn_credential
                    //         }
                    //     ]
                    // }
                });

                peer.addTransceiver('video', {direction: 'recvonly'})
                peer.addTransceiver('audio', {direction: 'recvonly'})

                this.handlePeerEvents(
                    peer,
                    incomingOffer,
                    broadcaster,
                    this.removeBroadcastVideo,
                )

                this.broadcasterPeer = peer;


            },
            handlePeerEvents(peer, incomingOffer, broadcaster, cleanupCallback){
                peer.on('signal', (data)=> {
                    console.log('from signal')
                    axios.post('/streaming-answer', {broadcaster, answer: data})
                        .then(res=>console.log(res))
                        .catch(err=>console.log(err))
                })

                peer.on('stream', (stream)=> {
                    console.log('streaming')
                    this.$refs.viewer.srcObject = stream;
                })

                peer.on('track', (track, stream)=> {
                    console.log('onTrack');
                })

                peer.on('connect', ()=> {
                    console.log('Viewer peer connected')
                })

                peer.on('close', ()=> {
                    console.log('Viewer peer closed.')
                    peer.destroy();
                    cleanupCallback();
                })

                peer.on('error', (err)=> {
                    console.log('peer error')
                })

                const updatedOffer = {
                    ...incomingOffer,
                    sdp: `${incomingOffer.sdp}\n`
                }

                peer.signal(updatedOffer);
            },
            initializeSignalOfferChannel(){
                console.log('inatialize signal offer channel')
                Echo.private(`stream-signal.channel.${this.auth_user_id}`)
                    .listen('StreamOffer', ({data})=> {
                        console.log('signal offer from private channel')

                        this.broadcasterId = data.broadcaster;
                        this.createViewerPeer(data.offer, data.broadcaster);
                    })
            },
            removeBroadcastVideo(){
                console.log('remove broadcast Video');
                alert('livestream ended by broadcaster');

                const tracks = this.$refs.viewer.srcObject.getTracks();
                tracks.forEach(track => {
                    track.stop();
                });

                this.$refs.viewer.srcObject = null;
            }
        }
    }
</script>

<style lang="scss" scoped>

</style>
