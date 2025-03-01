import { useState } from 'react';

export default function RightSidebar({ user }) {
    const [clients] = useState([
        { id: 1, name: 'AMITHA P', avatar: '/path/to/avatar1.jpg' },
        { id: 2, name: 'RAGHAV KHANNA', avatar: '/path/to/avatar2.jpg' },
        { id: 3, name: 'SOFIA AHMED', avatar: '/path/to/avatar3.jpg' },
        { id: 4, name: 'LIAM SMITH', avatar: '/path/to/avatar4.jpg' },
        { id: 5, name: 'AVA JONES', avatar: '/path/to/avatar5.jpg' },
        { id: 6, name: 'ETHAN PATEL', avatar: '/path/to/avatar6.jpg' },
        { id: 7, name: 'ZARA KHAN', avatar: '/path/to/avatar7.jpg' },
    ]);

    return (
        <div className="fixed right-0 top-0 h-full w-80 bg-[#1A1D1F] p-6 border-l border-gray-800">
            <div className="flex items-center justify-between mb-8">
                <div className="flex items-center space-x-3">
                    <img src={user.photo || '/path/to/default-avatar.jpg'} alt={user.name} className="h-12 w-12 rounded-full" />
                    <div>
                        <h4 className="font-unbounded-700">{user.name}</h4>
                        <p className="text-sm text-gray-500 font-unbounded-500">KOHLI</p>
                    </div>
                </div>
                <button className="text-2xl">⋯</button>
            </div>

            <h3 className="text-xl font-unbounded-700 mb-6">CLIENT LIST</h3>
            <div className="space-y-4 overflow-y-auto max-h-[calc(100vh-200px)]">
                {clients.map(client => (
                    <div key={client.id} className="flex items-center justify-between p-4 bg-[#111315] rounded-xl hover:bg-gray-800 cursor-pointer">
                        <div className="flex items-center space-x-3">
                            <img src={client.avatar} alt={client.name} className="h-10 w-10 rounded-full" />
                            <span className="font-unbounded-500">{client.name}</span>
                        </div>
                        <span className="text-gray-500">›</span>
                    </div>
                ))}
            </div>
        </div>
    );
} 