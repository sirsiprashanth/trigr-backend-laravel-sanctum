import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig';
import { collection, query, where, onSnapshot } from 'firebase/firestore';

export default function LeftSidebar({ onNavigate, currentView, currentUserId }) {
    const [pendingCount, setPendingCount] = useState(0);

    useEffect(() => {
        if (!currentUserId) return;

        const discoveryCallsCollection = collection(db, "discoveryCallRequest");
        const pendingCallsQuery = query(
            discoveryCallsCollection,
            where("coachId", "==", Number(currentUserId)),
            where("status", "==", "pending")
        );

        const unsubscribe = onSnapshot(pendingCallsQuery, (snapshot) => {
            setPendingCount(snapshot.docs.length);
        });

        return () => unsubscribe();
    }, [currentUserId]);

    return (
        <div className="fixed left-0 top-0 h-full w-64 bg-[#1A1D1F] p-6 border-r border-gray-800">
            <div className="mb-10">
                <img src="/path/to/logo.png" alt="TRIGR" className="h-8" />
            </div>
            
            <nav className="space-y-4">
                <button 
                    onClick={() => onNavigate('dashboard')}
                    className={`flex items-center space-x-3 px-4 py-3 w-full rounded-lg ${
                        currentView === 'dashboard' ? 'bg-[#85C240]' : 'hover:bg-gray-800'
                    }`}
                >
                    <span>📊</span>
                    <span className="font-unbounded-700">DASHBOARD</span>
                </button>
                <button 
                    onClick={() => onNavigate('messages')}
                    className={`flex items-center space-x-3 px-4 py-3 w-full rounded-lg ${
                        currentView === 'messages' ? 'bg-[#85C240]' : 'hover:bg-gray-800'
                    }`}
                >
                    <span>💬</span>
                    <span className="font-unbounded-500">MESSAGES</span>
                </button>
                <button 
                    onClick={() => onNavigate('schedules')}
                    className={`flex items-center space-x-3 px-4 py-3 w-full rounded-lg ${
                        currentView === 'schedules' ? 'bg-[#85C240]' : 'hover:bg-gray-800'
                    }`}
                >
                    <span>📅</span>
                    <span className="font-unbounded-500">SCHEDULES</span>
                </button>
                <button 
                    onClick={() => onNavigate('new-user')}
                    className={`flex items-center justify-between px-4 py-3 w-full rounded-lg ${
                        currentView === 'new-user' ? 'bg-[#85C240]' : 'hover:bg-gray-800'
                    }`}
                >
                    <div className="flex items-center space-x-3">
                        <span>👤</span>
                        <span className="font-unbounded-500">NEW USER</span>
                    </div>
                    {pendingCount > 0 && (
                        <span className="bg-[#85C240] px-2 py-1 rounded-full text-xs font-unbounded-600">
                            {pendingCount}
                        </span>
                    )}
                </button>
                <button 
                    onClick={() => onNavigate('todo')}
                    className={`flex items-center space-x-3 px-4 py-3 w-full rounded-lg ${
                        currentView === 'todo' ? 'bg-[#85C240]' : 'hover:bg-gray-800'
                    }`}
                >
                    <span>📝</span>
                    <span className="font-unbounded-500">TO-DO LIST</span>
                </button>
                <button 
                    onClick={() => onNavigate('coaching-plans')}
                    className={`flex items-center space-x-3 px-4 py-3 w-full rounded-lg ${
                        currentView === 'coaching-plans' ? 'bg-[#85C240]' : 'hover:bg-gray-800'
                    }`}
                >
                    <span>📋</span>
                    <span className="font-unbounded-500">MY PLANS</span>
                </button>
            </nav>

            <div className="absolute bottom-8 left-0 w-full px-6">
                <div className="bg-[#111315] p-4 rounded-xl">
                    <div className="flex items-center space-x-2 mb-4">
                        <span>💬</span>
                        <span className="font-unbounded-500">CHAT WITH</span>
                    </div>
                    <div className="flex items-center space-x-2">
                        <img src="/path/to/ai-avatar.png" alt="TRIGR AI" className="h-8 w-8 rounded-full" />
                        <span className="font-unbounded-600">TRIGR AI</span>
                    </div>
                </div>
                <button className="mt-4 w-full flex items-center justify-center space-x-2 px-4 py-3 bg-white bg-opacity-10 rounded-lg hover:bg-opacity-20">
                    <span>🚪</span>
                    <span className="font-unbounded-500">SIGN OUT</span>
                </button>
            </div>
        </div>
    );
} 