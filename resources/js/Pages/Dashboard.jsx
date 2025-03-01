import { Head } from '@inertiajs/react';
import { useState } from 'react';
import LeftSidebar from '@/Components/Dashboard/LeftSidebar';
import MainContent from '@/Components/Dashboard/MainContent';
import Messages from '@/Components/Dashboard/Messages';
import Schedules from '@/Components/Dashboard/Schedules';
import RightSidebar from '@/Components/Dashboard/RightSidebar';

export default function Dashboard({ auth }) {
    const [currentView, setCurrentView] = useState('dashboard');

    const handleNavigation = (view) => {
        setCurrentView(view);
    };

    const renderMainContent = () => {
        switch (currentView) {
            case 'dashboard':
                return <MainContent currentUserId={auth.user.id} onNavigate={handleNavigation} />;
            case 'messages':
                return <Messages currentUserId={auth.user.id} />;
            case 'schedules':
                return <Schedules currentUserId={auth.user.id} />;
            default:
                return <MainContent currentUserId={auth.user.id} onNavigate={handleNavigation} />;
        }
    };

    return (
        <div className="flex min-h-screen bg-[#1A1D1F] text-white">
            <Head title="Dashboard" />
            <LeftSidebar onNavigate={handleNavigation} currentView={currentView} />
            {renderMainContent()}
            <RightSidebar user={auth.user} />
        </div>
    );
}
