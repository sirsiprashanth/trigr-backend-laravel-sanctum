import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig';
import { collection, query, where, orderBy, getDocs, serverTimestamp } from 'firebase/firestore';
import axios from 'axios';
import ChatScreen from './ChatScreen';

export default function Messages({ currentUserId }) {
    const [chatHistory, setChatHistory] = useState([]);
    const [loading, setLoading] = useState(true);
    const [userNames, setUserNames] = useState({});
    const [selectedRecipient, setSelectedRecipient] = useState(null);
    const [unreadCounts, setUnreadCounts] = useState({});

    useEffect(() => {
        const fetchChatHistory = async () => {
            try {
                const messagesCollection = collection(db, "messages");
                
                // Query for all messages where the current user is a participant
                const messagesQuery = query(
                    messagesCollection,
                    where("participants", "array-contains", Number(currentUserId)),
                    orderBy("createdAt", "desc")
                );

                const querySnapshot = await getDocs(messagesQuery);
                
                // Group messages by conversation and count unread messages
                const conversations = {};
                const messageCountsTemp = {};

                querySnapshot.forEach((doc) => {
                    const message = { id: doc.id, ...doc.data() };
                    const participantsKey = message.participants.sort().join('-');
                    
                    // Count unread messages (where recipientId is currentUserId and message is not read)
                    if (message.recipientId === Number(currentUserId) && !message.read) {
                        messageCountsTemp[message.userId] = (messageCountsTemp[message.userId] || 0) + 1;
                    }
                    
                    if (!conversations[participantsKey]) {
                        conversations[participantsKey] = {
                            participants: message.participants,
                            lastMessage: message.text,
                            timestamp: message.createdAt,
                            recipientId: message.participants.find(id => id !== Number(currentUserId))
                        };
                    }
                });

                // Fetch user names for all participants
                const uniqueUserIds = [...new Set(Object.values(conversations)
                    .map(conv => conv.recipientId))];
                
                const userNamesMap = {};
                await Promise.all(uniqueUserIds.map(async (uid) => {
                    try {
                        const response = await axios.get(`/api/users/${uid}`);
                        userNamesMap[uid] = response.data.name;
                    } catch (error) {
                        console.error(`Error fetching user ${uid} details:`, error);
                        userNamesMap[uid] = 'Unknown User';
                    }
                }));

                setUnreadCounts(messageCountsTemp);
                setUserNames(userNamesMap);
                setChatHistory(Object.values(conversations));
                setLoading(false);
            } catch (error) {
                console.error("Error fetching chat history:", error);
                setLoading(false);
            }
        };

        fetchChatHistory();
    }, [currentUserId]);

    const handleChatPress = (recipientId) => {
        setSelectedRecipient(recipientId);
        // Clear unread count for this recipient
        setUnreadCounts(prev => ({
            ...prev,
            [recipientId]: 0
        }));
    };

    if (selectedRecipient) {
        return (
            <ChatScreen 
                currentUserId={currentUserId}
                recipientId={selectedRecipient}
                onClose={() => setSelectedRecipient(null)}
            />
        );
    }

    if (loading) {
        return (
            <div className="flex-1 ml-64 mr-80 p-8">
                <div className="flex flex-col items-center justify-center h-full">
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#85C240]"></div>
                    <p className="mt-4 text-gray-400 font-unbounded-500">Loading chat history...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="flex-1 ml-64 mr-80">
            <div className="p-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-4xl font-unbounded-900">MESSAGES</h1>
                    <h2 className="text-4xl font-unbounded-700 text-gray-500">CHAT HISTORY</h2>
                </div>

                {/* Search Bar */}
                <div className="mb-6">
                    <div className="relative">
                        <input
                            type="text"
                            placeholder="Search for your messages"
                            className="w-full bg-[#111315] text-white rounded-xl px-4 py-3 pl-10 font-unbounded-400 focus:outline-none"
                        />
                        <span className="absolute left-3 top-1/2 transform -translate-y-1/2">🔍</span>
                    </div>
                </div>

                {/* Chat List */}
                <div className="space-y-4">
                    {chatHistory.map((chat, index) => (
                        <div 
                            key={index}
                            onClick={() => handleChatPress(chat.recipientId)}
                            className="bg-[#111315] p-6 rounded-xl hover:bg-gray-800 cursor-pointer transition-all duration-300"
                        >
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    <div className="bg-[#FF7A50] p-3 rounded-xl">
                                        <span className="text-xl">💬</span>
                                    </div>
                                    <div>
                                        <h4 className="font-unbounded-700 text-lg">
                                            {userNames[chat.recipientId] || 'Loading...'}
                                        </h4>
                                        <p className="text-sm text-gray-400 font-unbounded-400">
                                            {chat.lastMessage}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex flex-col items-end space-y-2">
                                    <span className="text-sm text-gray-500 font-unbounded-400">
                                        {chat.timestamp ? new Date(chat.timestamp.toDate()).toLocaleString('en-US', {
                                            hour: 'numeric',
                                            minute: 'numeric'
                                        }) : 'No date'}
                                    </span>
                                    {unreadCounts[chat.recipientId] > 0 && (
                                        <span className="bg-[#85C240] px-2 py-1 rounded-full text-xs font-unbounded-600">
                                            {unreadCounts[chat.recipientId]}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
} 