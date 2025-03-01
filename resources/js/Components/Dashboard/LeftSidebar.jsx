export default function LeftSidebar({ onNavigate, currentView }) {
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
                    className="flex items-center space-x-3 px-4 py-3 w-full hover:bg-gray-800 rounded-lg"
                >
                    <span>📅</span>
                    <span className="font-unbounded-500">SCHEDULES</span>
                </button>
                <button 
                    onClick={() => onNavigate('new-user')}
                    className="flex items-center space-x-3 px-4 py-3 w-full hover:bg-gray-800 rounded-lg"
                >
                    <span>👤</span>
                    <span className="font-unbounded-500">NEW USER</span>
                </button>
                <button 
                    onClick={() => onNavigate('todo')}
                    className="flex items-center space-x-3 px-4 py-3 w-full hover:bg-gray-800 rounded-lg"
                >
                    <span>📝</span>
                    <span className="font-unbounded-500">TO-DO LIST</span>
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