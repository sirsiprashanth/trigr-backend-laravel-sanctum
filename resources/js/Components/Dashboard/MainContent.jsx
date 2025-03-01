import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig'; // Import your Firebase configuration
import { collection, query, where, onSnapshot } from 'firebase/firestore';

export default function MainContent({ currentUserId, onNavigate }) {
    const [tasks, setTasks] = useState([
        { id: 1, title: 'NEW USER', count: '99+', subtitle: 'Users Inbound', icon: '👤', bgColor: 'bg-[#85C240]' },
        { id: 2, title: 'CLIENTS', count: '99+', subtitle: 'Clients Available', icon: '👥', bgColor: 'bg-[#9747FF]' },
        { id: 3, title: 'MESSAGES', count: 0, subtitle: 'Messages', icon: '💬', bgColor: 'bg-[#FF7A50]', onClick: () => onNavigate('messages') },
        { id: 4, title: 'SCHEDULE', count: '99+', subtitle: 'Upcoming Tasks', icon: '📅', bgColor: 'bg-[#50B5FF]' },
    ]);

    const [upcomingTasks, setUpcomingTasks] = useState([
        { 
            id: 1, 
            type: 'message', 
            title: 'NO UNREAD MESSAGES', 
            subtitle: 'NO NEW MESSAGES',
            time: 'NOW',
            icon: '💬',
            bgColor: 'bg-[#FF7A50]'
        },
        { 
            id: 2, 
            type: 'user', 
            title: '2 NEW USERS', 
            subtitle: 'NEW USERS INBOUND',
            time: '2M AGO',
            icon: '👤',
            bgColor: 'bg-[#85C240]'
        },
        { 
            id: 3, 
            type: 'discovery', 
            title: 'DISCOVERY CALL WITH ARCHANA', 
            subtitle: 'CLICK HERE TO START',
            time: '3M AGO',
            icon: '📞',
            bgColor: 'bg-[#50B5FF]'
        },
        { 
            id: 4, 
            type: 'workout', 
            title: 'PREPARE WORKOUT PLAN FOR SANJAY', 
            subtitle: '',
            time: '3M AGO',
            icon: '💪',
            bgColor: 'bg-[#9747FF]'
        },
    ]);

    useEffect(() => {
        if (!currentUserId) return;

        // Query for unread messages where the current user is the recipient
        const messagesCollection = collection(db, "messages");
        const unreadMessagesQuery = query(
            messagesCollection,
            where("recipientId", "==", Number(currentUserId)),
            where("read", "==", false)
        );

        const unsubscribe = onSnapshot(unreadMessagesQuery, (snapshot) => {
            const unreadCount = snapshot.docs.length;
            
            // Update the messages task card
            setTasks(prevTasks => 
                prevTasks.map(task => 
                    task.title === 'MESSAGES' 
                        ? { ...task, count: unreadCount } 
                        : task
                )
            );

            // Update the first upcoming task if there are unread messages
            setUpcomingTasks(prevTasks => {
                const updatedTasks = [...prevTasks];
                updatedTasks[0] = {
                    ...updatedTasks[0],
                    title: unreadCount > 0 ? `${unreadCount} UNREAD MESSAGES` : 'NO UNREAD MESSAGES',
                    subtitle: unreadCount > 0 ? 'NEW MESSAGES FROM CLIENTS' : 'NO NEW MESSAGES'
                };
                return updatedTasks;
            });
        });

        return () => unsubscribe();
    }, [currentUserId]);

    return (
        <div className="flex-1 ml-64 mr-80">
            <div className="p-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-4xl font-unbounded-900 unbounded">GET STRONGER</h1>
                    <h2 className="text-4xl font-unbounded-700 unbounded text-gray-500">EVERYDAY!</h2>
                </div>

                {/* Task Cards Grid */}
                <div className="mb-12">
                    <h3 className="text-xl font-unbounded-700 mb-6">ALL TASKS</h3>
                    <div className="grid grid-cols-2 gap-6">
                        {tasks.map(task => (
                            <div key={task.id} className={`${task.bgColor} bg-opacity-10 p-6 rounded-xl`} onClick={task.onClick}>
                                <div className="flex items-center justify-between mb-4">
                                    <span className="text-2xl">{task.icon}</span>
                                    <span className={`${task.bgColor} px-3 py-1 rounded-full text-sm font-unbounded-600`}>
                                        {task.count}
                                    </span>
                                </div>
                                <h4 className="text-lg font-unbounded-700 mb-1">{task.title}</h4>
                                <p className="text-sm text-gray-400 font-unbounded-400">{task.subtitle}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Upcoming Tasks */}
                <div>
                    <div className="flex items-center justify-between mb-6">
                        <h3 className="text-xl font-unbounded-700">UPCOMING TASKS</h3>
                        <div className="flex items-center space-x-4">
                            <span className="text-gray-500 font-unbounded-400">EARLIER TODAY (8)</span>
                            <button className="text-gray-500 hover:text-white font-unbounded-400">clear</button>
                            <button className="text-gray-500 hover:text-white font-unbounded-400">PAST</button>
                        </div>
                    </div>
                    <div className="space-y-4">
                        {upcomingTasks.map(task => (
                            <div key={task.id} className="flex items-center space-x-4 bg-[#111315] p-4 rounded-xl">
                                <div className={`${task.bgColor} p-3 rounded-xl`}>
                                    <span className="text-xl">{task.icon}</span>
                                </div>
                                <div className="flex-1">
                                    <h4 className="font-unbounded-700">{task.title}</h4>
                                    <p className="text-sm text-gray-400 font-unbounded-400">{task.subtitle}</p>
                                </div>
                                <span className="text-sm text-gray-500 font-unbounded-400">{task.time}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
} 