import { useState, useEffect, useRef } from 'react';
import { db } from '../../firebaseConfig';
import {
    collection,
    addDoc,
    onSnapshot,
    query,
    orderBy,
    where,
    serverTimestamp,
    updateDoc,
    doc,
} from 'firebase/firestore';

export default function ChatScreen({ currentUserId, recipientId, onClose }) {
    const [message, setMessage] = useState('');
    const [messages, setMessages] = useState([]);
    const messagesEndRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        if (!recipientId) return;

        const fetchMessages = async () => {
            try {
                const messagesCollection = collection(db, 'messages');
                const messagesQuery = query(
                    messagesCollection,
                    where('participants', 'array-contains', Number(currentUserId)),
                    orderBy('createdAt', 'asc')
                );

                const unsubscribe = onSnapshot(messagesQuery, async (snapshot) => {
                    const messagesList = snapshot.docs
                        .map((doc) => ({
                            id: doc.id,
                            ...doc.data(),
                        }))
                        .filter((msg) => 
                            msg.participants.includes(Number(recipientId))
                        );

                    // Mark unread messages as read
                    const unreadMessages = messagesList.filter(
                        msg => msg.recipientId === Number(currentUserId) && !msg.read
                    );

                    // Update each unread message
                    await Promise.all(unreadMessages.map(async (msg) => {
                        const messageRef = doc(db, 'messages', msg.id);
                        await updateDoc(messageRef, { read: true });
                    }));

                    setMessages(messagesList);
                    scrollToBottom();
                });

                return unsubscribe;
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        };

        fetchMessages();
    }, [currentUserId, recipientId]);

    const handleSend = async () => {
        if (message.trim() && currentUserId) {
            try {
                const sortedParticipants = [Number(currentUserId), Number(recipientId)].sort((a, b) => a - b);

                await addDoc(collection(db, 'messages'), {
                    text: message,
                    createdAt: serverTimestamp(),
                    userId: Number(currentUserId),
                    recipientId: Number(recipientId),
                    participants: sortedParticipants,
                    read: false,
                });
                setMessage('');
            } catch (error) {
                console.error('Error sending message: ', error);
            }
        }
    };

    if (!recipientId) {
        return (
            <div className="flex-1 ml-64 mr-80 flex flex-col items-center justify-center bg-[#24262B] p-8">
                <div className="text-center space-y-4">
                    <h2 className="text-2xl font-unbounded-900">TO START A</h2>
                    <h2 className="text-2xl font-unbounded-900">CONVERSATION</h2>
                    <p className="text-xl font-unbounded-400">PLEASE HIRE A COACH.</p>
                    <button className="mt-6 px-6 py-3 bg-black border border-[#00D004] rounded-xl font-unbounded-500 hover:bg-gray-900">
                        HIRE A COACH →
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex-1 ml-64 mr-80 flex flex-col h-screen bg-[#24262B]">
            {/* Header */}
            <div className="flex items-center justify-between p-6 border-b border-gray-700">
                <button 
                    onClick={onClose}
                    className="text-gray-400 hover:text-white font-unbounded-500"
                >
                    ← Back
                </button>
                <h2 className="text-xl font-unbounded-700">Chat</h2>
                <div className="w-10"></div> {/* Spacer for alignment */}
            </div>

            {/* Messages */}
            <div className="flex-1 overflow-y-auto p-6 space-y-4">
                {messages.map((msg) => (
                    <div
                        key={msg.id}
                        className={`flex ${
                            msg.userId === Number(currentUserId) ? 'justify-end' : 'justify-start'
                        }`}
                    >
                        <div
                            className={`max-w-[70%] rounded-xl px-4 py-2 ${
                                msg.userId === Number(currentUserId)
                                    ? 'bg-[#9CC94D] text-white'
                                    : 'bg-[#393C43] text-white'
                            }`}
                        >
                            <p className="font-unbounded-400 text-sm">{msg.text}</p>
                        </div>
                    </div>
                ))}
                <div ref={messagesEndRef} />
            </div>

            {/* Input */}
            <div className="p-4 border-t border-gray-700">
                <div className="flex items-center space-x-2">
                    <input
                        type="text"
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        onKeyPress={(e) => e.key === 'Enter' && handleSend()}
                        placeholder="Type a message"
                        className="flex-1 bg-[#111315] text-white rounded-xl px-4 py-2 font-unbounded-400 focus:outline-none focus:ring-1 focus:ring-[#00D004]"
                    />
                    <button
                        onClick={handleSend}
                        className="bg-[#00D004] text-white rounded-full w-10 h-10 flex items-center justify-center hover:bg-[#00B003]"
                    >
                        →
                    </button>
                </div>
            </div>
        </div>
    );
} 