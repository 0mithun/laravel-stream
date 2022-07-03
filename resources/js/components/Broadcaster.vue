<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <button class="btn btn-success" @click="startStream">Start Stream</button><br>
                <p class="my-5">Share the following link: {{ streamLink }}</p>
                <video autoplay ref="broadcaster"></video>
            </div>
        </div>
    </div>
</template>

<script>
    import Peer from 'simple-peer';
    import {getPermission} from '../helpers'
    export default {
        name: 'broadcaster',
        props: [
            'auth_user_id', 'env', 'turn_url', 'turn_credential'
        ],
        data(){
            return {
                isVisibleLink: false,
                streamPresenceChannel: null,
                streamingUsers: [],
                currentlyConnectedUser: null,
                allPeers: {}
            }
        },
        computed: {
            streamId(){
                return `${this.auth_user_id}45464`;
            },
            streamLink(){
                if(this.env == 'production'){
                    return `laravel-video.dev/streaming/${this.streamId}`
                }else {
                    return `http://127.0.0.1:8000/streaming/${this.streamId}`
                }
            }
        },
        methods: {
            async startStream(){
                const stream = await getPermission();
                this.$refs.broadcaster.srcObject = stream;

                this.initializeStreamingChannel();
                this.initializeSignalAnswerChannel();
                this.isVisibleLink = true;
            },
            peerCreator(stream, user, signalCallback){
                let peer;

                return {
                    create: ()=> {
                        peer = new Peer({
                            initiator: true,
                            trickle: false,
                            stream: stream,
                            config: {
                                iceServices: [
                                    {
                                        urls: 'stun:stun.stunprotocol.org'
                                    },
                                    {
                                        urls: this.turn_url,
                                        username: this.turn_username,
                                        credential: this.turn_credential,
                                    }
                                ]
                            }
                        })
                    },
                    getPeer: ()=> peer,
                    initEvents: ()=> {
                        peer.on('signal', (data)=> {
                            console.log('signal getting')
                            signalCallback(data, user)
                        });

                        peer.on('stream', (stream) => {
                            console.log('onStream')
                        });

                        peer.on('track', (track, stream)=> {
                            console.log('on track');
                        });

                        peer.on('connect', ()=> {
                            console.log('Broadcaster peer connected')
                        });

                        peer.on('close', ()=> {
                            console.log('broadcaster peer closed')
                        })

                        peer.on('error', ()=> {
                            console.log('peer error occured.')
                        })
                    }
                }
            },
            initializeStreamingChannel(){
                this.streamPresenceChannel = Echo.join(`stream-channel.${this.streamId}`)
                this.streamPresenceChannel.here((users)=> {
                    console.log('here')
                    this.streamingUsers = users;
                })

                this.streamPresenceChannel.joining((user)=> {
                    console.log('new user', user);
                    const joiningUserIndex = this.streamingUsers.findIndex(item=> item.id === user.id);
                    console.log('joining index', joiningUserIndex);
                    if(joiningUserIndex < 0){
                        this.streamingUsers.push(user);

                        this.currentlyConnectedUser = user.id;

                        console.log('bottom currently connected user')
                        this.$set(this.allPeers, `${user.id}`, this.peerCreator(this.$refs.broadcaster.srcObject, user, this.signalCallback))

                        this.allPeers[user.id].create();
                        this.allPeers[user.id].initEvents();

                        console.log('end line')
                    }
                })

                this.streamPresenceChannel.leaving((user)=> {
                    console.log(user.name, 'left')
                    this.allPeers[user.id].getPeer().destroy();

                    delete this.allPeers[user.id]

                    if(user.id === this.auth_user_id){
                        this.streamingUsers = [];
                    }else {
                        const leavingUserIndex = this.streamingUsers.findIndex((item)=> item.id === user.id)
                        this.streamingUsers.splice(leavingUserIndex, 1);
                    }
                })
            },
            initializeSignalAnswerChannel(){
                Echo.private(`stream-signal.channel.${this.auth_user_id}`)
                    .listen('StreamAnswer', ({data})=> {
                        console.log('signal answer from private channel')
                        if(data.answer.renegotiate){
                            console.log('renegotiate');
                        }
                        if(data.answer.sdp){
                            const updatedSignal = {
                                ...data.answer,
                                sdp: `${data.answer.sdp}\n`
                            }

                            this.allPeers[this.currentlyConnectedUser]
                                .getPeer()
                                .signal(updatedSignal)
                        }
                    })
            },
            signalCallback(offer, user){
                axios.post('/streaming-offer', {
                    broadcaster: this.auth_user_id,
                    receiver: user,
                    offer,
                })
                .then(res=> {
                    console.log(res)
                })
                .catch(err=>console.log(err))
                ;
            }
        }
    }
</script>

<style lang="scss" scoped>

</style>
