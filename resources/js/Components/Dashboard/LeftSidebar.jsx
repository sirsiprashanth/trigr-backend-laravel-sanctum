import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig';
import { collection, query, where, onSnapshot } from 'firebase/firestore';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTimes } from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/react';

export default function LeftSidebar({ onNavigate, currentView, currentUserId }) {
    const [pendingCount, setPendingCount] = useState(0);
    const [showLogoutConfirmation, setShowLogoutConfirmation] = useState(false);
    const [isLoggingOut, setIsLoggingOut] = useState(false);

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

    const handleLogout = () => {
        // Show loading state
        setIsLoggingOut(true);
        
        // Use Inertia.js router for logout (standard Laravel Breeze approach)
        router.post('/logout', {}, {
            onFinish: () => {
                // The page will automatically redirect on successful logout
                // This is just a fallback in case something goes wrong
                setTimeout(() => {
                    setIsLoggingOut(false);
                    alert('Logout process took too long. Please try again.');
                }, 5000);
            },
            onError: () => {
                setIsLoggingOut(false);
                alert('Failed to logout. Please try again.');
            }
        });
    };

    return (
        <div className="fixed left-0 top-0 h-full w-64 bg-[#1A1D1F] p-6 border-r border-gray-800">
            <div className="mb-10">
                <h1 className="text-xl font-bold text-white">TRIGR</h1>
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
                        <span className="text-xl">🤖</span>
                        <span className="font-unbounded-600">TRIGR AI</span>
                    </div>
                </div>
                <button 
                    onClick={() => setShowLogoutConfirmation(true)}
                    className="mt-4 w-full flex items-center justify-center space-x-2 px-4 py-3 bg-white bg-opacity-10 rounded-lg hover:bg-opacity-20"
                >
                    <span>🚪</span>
                    <span className="font-unbounded-500">SIGN OUT</span>
                </button>
            </div>

            {/* Logout Confirmation Modal */}
            {showLogoutConfirmation && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-[#111315] rounded-xl p-6 w-full max-w-md">
                        <div className="flex items-center justify-between mb-6">
                            <h2 className="text-2xl font-unbounded-700">SIGN OUT</h2>
                            <button 
                                onClick={() => setShowLogoutConfirmation(false)} 
                                className="text-gray-500 hover:text-white"
                                disabled={isLoggingOut}
                            >
                                <FontAwesomeIcon icon={faTimes} />
                            </button>
                        </div>
                        
                        <p className="mb-6 text-gray-400">
                            Are you sure you want to sign out? Any unsaved changes will be lost.
                        </p>
                        
                        <div className="flex space-x-4">
                            <button
                                onClick={() => setShowLogoutConfirmation(false)}
                                className="flex-1 px-4 py-3 bg-gray-800 text-white rounded-lg font-unbounded-600 hover:bg-gray-700 transition-all duration-300"
                                disabled={isLoggingOut}
                            >
                                CANCEL
                            </button>
                            <button
                                onClick={handleLogout}
                                className="flex-1 px-4 py-3 bg-[#85C240] text-white rounded-lg font-unbounded-600 hover:bg-opacity-90 transition-all duration-300"
                                disabled={isLoggingOut}
                            >
                                {isLoggingOut ? (
                                    <div className="flex items-center justify-center">
                                        <div className="w-5 h-5 border-t-2 border-b-2 border-white rounded-full animate-spin mr-2"></div>
                                        SIGNING OUT...
                                    </div>
                                ) : 'SIGN OUT'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
} 