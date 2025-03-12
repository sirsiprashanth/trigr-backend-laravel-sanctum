import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig';
import { collection, query, where, onSnapshot, orderBy, doc, deleteDoc, updateDoc } from 'firebase/firestore';
import axios from 'axios';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrash, faEdit, faCalendar, faTimes, faComment } from '@fortawesome/free-solid-svg-icons';
import ChatScreen from './ChatScreen';

export default function NewUser({ currentUserId }) {
    const [loading, setLoading] = useState(true);
    const [requests, setRequests] = useState([]);
    const [isEditing, setIsEditing] = useState(false);
    const [editingRequest, setEditingRequest] = useState(null);
    const [showChat, setShowChat] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState(null);

    useEffect(() => {
        if (!currentUserId) return;

        const fetchRequests = async () => {
            try {
                const discoveryCallsCollection = collection(db, "discoveryCallRequest");
                const requestsQuery = query(
                    discoveryCallsCollection,
                    where("coachId", "==", Number(currentUserId)),
                    where("status", "==", "pending"),
                    orderBy("createdAt", "desc")
                );

                const unsubscribe = onSnapshot(requestsQuery, async (snapshot) => {
                    const requestsList = [];
                    
                    for (const doc of snapshot.docs) {
                        const data = doc.data();
                        try {
                            // Fetch user name for each request
                            const response = await axios.get(`/api/users/${data.userId}`);
                            const userName = response.data.name;

                            // Format date and time
                            const formattedTime = new Date(data.preferred_time).toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: 'numeric',
                                hour12: true
                            });

                            const formattedDate = new Date(data.preferred_date).toLocaleDateString('en-US', {
                                month: 'long',
                                day: 'numeric',
                                year: 'numeric'
                            });

                            requestsList.push({
                                id: doc.id,
                                name: userName,
                                time: formattedTime,
                                date: formattedDate,
                                type: 'discovery call',
                                topic: data.topic,
                                rawDate: data.preferred_date,
                                rawTime: data.preferred_time,
                                status: data.status,
                                userId: data.userId,
                                coachId: data.coachId,
                                meetingLink: data.meetingLink || ''
                            });
                        } catch (error) {
                            console.error("Error fetching user details:", error);
                        }
                    }
                    setRequests(requestsList);
                    setLoading(false);
                });

                return () => unsubscribe();
            } catch (error) {
                console.error("Error fetching requests:", error);
                setLoading(false);
            }
        };

        fetchRequests();
    }, [currentUserId]);

    const handleDelete = async (requestId) => {
        try {
            await deleteDoc(doc(db, "discoveryCallRequest", requestId));
        } catch (error) {
            console.error("Error deleting request:", error);
        }
    };

    const handleEdit = (request) => {
        setEditingRequest(request);
        setIsEditing(true);
    };

    const handleEditClose = () => {
        setIsEditing(false);
        setEditingRequest(null);
    };

    const handleEditSave = async (requestId, updatedData) => {
        try {
            const requestRef = doc(db, 'discoveryCallRequest', requestId);
            await updateDoc(requestRef, {
                topic: updatedData.topic,
                preferred_date: updatedData.preferred_date,
                preferred_time: updatedData.preferred_time,
                status: updatedData.status,
                meetingLink: updatedData.meetingLink || ''
            });
            setIsEditing(false);
            setEditingRequest(null);
        } catch (error) {
            console.error('Error updating request:', error);
        }
    };

    const getTaskTypeStyle = (type) => {
        return type === 'discovery call' ? 'bg-[#50B5FF] text-white' : '';
    };

    // Edit Task Component
    const EditTask = ({ request, onClose, onSave }) => {
        const [taskData, setTaskData] = useState({
            topic: request.topic,
            date: request.rawDate,
            time: request.rawTime,
            status: request.status || 'pending',
            meetingLink: request.meetingLink || ''
        });

        const handleSave = () => {
            if (taskData.status === 'accepted' && !taskData.meetingLink) {
                alert('Please provide a meeting link for accepted discovery calls');
                return;
            }
            onSave(request.id, {
                topic: taskData.topic,
                preferred_date: taskData.date,
                preferred_time: taskData.time,
                status: taskData.status,
                meetingLink: taskData.meetingLink
            });
        };

        const handleChat = () => {
            setSelectedUserId(request.userId);
            setShowChat(true);
            onClose();
        };

        return (
            <div className="bg-[#111315] rounded-xl p-6">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-2xl font-unbounded-700">EDIT REQUEST</h2>
                    <div className="flex items-center space-x-4">
                        <button onClick={onClose} className="text-gray-500 hover:text-white transition-colors duration-300">
                            <FontAwesomeIcon icon={faTimes} />
                        </button>
                    </div>
                </div>

                <div className="mb-6">
                    <p className="text-lg font-unbounded-500">{request.name}</p>
                    <span className="inline-block px-3 py-1 bg-[#85C240] text-white rounded-lg text-sm font-unbounded-500 mt-2">
                        discovery call
                    </span>
                </div>

                <div className="space-y-6">
                    <div>
                        <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Task Title*</label>
                        <input
                            type="text"
                            value={taskData.topic}
                            onChange={(e) => setTaskData({ ...taskData, topic: e.target.value })}
                            className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                            placeholder="Enter Title"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Status</label>
                        <select
                            value={taskData.status}
                            onChange={(e) => setTaskData({ ...taskData, status: e.target.value })}
                            className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                        >
                            <option value="pending">PENDING</option>
                            <option value="accepted">ACCEPTED</option>
                            <option value="cancelled">CANCELLED</option>
                        </select>
                    </div>

                    {taskData.status === 'accepted' && (
                        <div>
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Meeting Link*</label>
                            <input
                                type="url"
                                value={taskData.meetingLink || ''}
                                onChange={(e) => setTaskData({ ...taskData, meetingLink: e.target.value })}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                placeholder="Enter Zoom/Google Meet link"
                                required
                            />
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Date</label>
                            <input
                                type="date"
                                value={taskData.date}
                                onChange={(e) => setTaskData({ ...taskData, date: e.target.value })}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Time</label>
                            <input
                                type="time"
                                value={taskData.time}
                                onChange={(e) => setTaskData({ ...taskData, time: e.target.value })}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                            />
                        </div>
                    </div>

                    <button 
                            onClick={handleChat}
                            className="w-full items-center space-x-2 px-4 py-2 bg-[#FF7A50] text-white rounded-lg hover:bg-opacity-90 transition-all duration-300"
                        >
                            <FontAwesomeIcon icon={faComment} className="mr-2" />
                            <span className="font-unbounded-500">CHAT</span>
                        </button>
                    <button
                        onClick={handleSave}
                        className="w-full bg-[#85C240] text-white py-3 rounded-lg font-unbounded-600 hover:bg-opacity-90 transition-all duration-300"
                    >
                        SAVE CHANGES
                    </button>
                </div>
            </div>
        );
    };

    if (loading) {
        return (
            <div className="flex-1 ml-64 mr-80 flex items-center justify-center">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#85C240]"></div>
            </div>
        );
    }

    if (showChat) {
        return (
            <ChatScreen 
                currentUserId={currentUserId}
                recipientId={selectedUserId}
                onClose={() => {
                    setShowChat(false);
                    setSelectedUserId(null);
                }}
            />
        );
    }

    return (
        <div className="flex-1 ml-64 mr-80">
            <div className="p-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-4xl font-unbounded-900">NEW USER</h1>
                    <h2 className="text-4xl font-unbounded-700 text-gray-500">DISCOVERY CALLS</h2>
                </div>

                {/* Request List */}
                <div className="space-y-4">
                    {isEditing ? (
                        <EditTask
                            request={editingRequest}
                            onClose={handleEditClose}
                            onSave={handleEditSave}
                        />
                    ) : (
                        requests.length === 0 ? (
                            <div className="text-center text-gray-500 py-8">
                                <p className="font-unbounded-500">No pending discovery calls</p>
                            </div>
                        ) : (
                            requests.map((request) => (
                                <div key={request.id} className="flex items-center bg-[#111315] p-4 rounded-xl">
                                    <input
                                        type="checkbox"
                                        className="mr-4 h-5 w-5 rounded border-gray-600 bg-transparent"
                                    />
                                    <div className="flex-1">
                                        <h4 className="font-unbounded-700">{request.name}</h4>
                                        <p className="text-sm text-gray-400 mt-1">{request.topic}</p>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <span className="text-sm text-gray-500">
                                                {request.time}
                                            </span>
                                            <span className="text-sm text-gray-500">•</span>
                                            <span className="text-sm text-gray-500">
                                                {request.date}
                                            </span>
                                        </div>
                                    </div>
                                    <div className={`px-4 py-2 rounded-lg text-sm font-unbounded-500 ${getTaskTypeStyle(request.type)}`}>
                                        {request.type.toUpperCase()}
                                    </div>
                                    <button 
                                        className="ml-4 text-gray-400 hover:text-gray-300"
                                        onClick={() => handleEdit(request)}
                                    >
                                        <FontAwesomeIcon icon={faEdit} className="text-light-gray" />
                                    </button>
                                    <button 
                                        className="ml-4 text-red-400 hover:text-red-300" 
                                        onClick={() => handleDelete(request.id)}
                                    >
                                        <FontAwesomeIcon icon={faTrash} />
                                    </button>
                                </div>
                            ))
                        )
                    )}
                </div>
            </div>
        </div>
    );
} 