import { useState, useEffect } from 'react';

export default function Schedules({ currentUserId }) {
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [selectedFilter, setSelectedFilter] = useState('ALL TASKS');
    const [schedules, setSchedules] = useState([
        {
            id: 1,
            name: 'Archana Mannan',
            time: '7:30 AM',
            date: 'FEB 4',
            type: 'discovery call'
        },
        {
            id: 2,
            name: 'Meeting with Sara',
            time: '7:30 AM',
            date: 'FEB 4',
            type: 'check in'
        },
        {
            id: 3,
            name: 'Sandeep Pai',
            time: '7:30 AM',
            date: 'FEB 4',
            type: 'discovery call'
        },
        {
            id: 4,
            name: 'Nutrition plan for Sarath',
            time: '7:30 AM',
            date: 'FEB 4',
            type: 'action plans'
        },
        {
            id: 5,
            name: 'Dietary Strategy for Sarath',
            time: '7:30 AM',
            date: 'FEB 4',
            type: 'check in'
        },
        {
            id: 6,
            name: 'Workout plan for Srimath',
            time: '7:30 AM',
            date: 'FEB 4',
            type: 'action plans'
        }
    ]);

    // Generate array of dates for the calendar
    const generateCalendarDates = () => {
        const dates = [];
        const today = new Date();
        for (let i = -3; i <= 4; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            dates.push(date);
        }
        return dates;
    };

    const getTaskTypeStyle = (type) => {
        switch (type) {
            case 'discovery call':
                return 'bg-[#85C240] text-white';
            case 'check in':
                return 'bg-[#FF7A50] text-white';
            case 'action plans':
                return 'bg-[#9747FF] text-white';
            default:
                return 'bg-gray-500 text-white';
        }
    };

    return (
        <div className="flex-1 ml-64 mr-80">
            <div className="p-8">
                {/* Date Navigation */}
                <div className="mb-8 bg-[#111315] rounded-xl p-6">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-2xl font-unbounded-700">TODAY'S TASK</h2>
                        <div className="flex items-center space-x-4">
                            <button className="text-gray-500 hover:text-white">
                                <span className="text-xl">←</span>
                            </button>
                            <span className="text-xl font-unbounded-700">
                                {selectedDate.toLocaleDateString('en-US', { 
                                    month: 'long',
                                    day: 'numeric',
                                    year: 'numeric'
                                }).toUpperCase()}
                            </span>
                            <button className="text-gray-500 hover:text-white">
                                <span className="text-xl">→</span>
                            </button>
                        </div>
                    </div>

                    {/* Calendar Strip */}
                    <div className="flex justify-between mb-6">
                        {generateCalendarDates().map((date, index) => (
                            <button
                                key={index}
                                onClick={() => setSelectedDate(date)}
                                className={`flex flex-col items-center p-4 rounded-xl ${
                                    date.toDateString() === selectedDate.toDateString()
                                        ? 'bg-[#85C240] text-white'
                                        : 'text-gray-500 hover:bg-[#222527]'
                                }`}
                            >
                                <span className="text-sm font-unbounded-500">
                                    {date.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase()}
                                </span>
                                <span className="text-2xl font-unbounded-700 mt-1">
                                    {date.getDate()}
                                </span>
                            </button>
                        ))}
                    </div>

                    {/* Filter Buttons */}
                    <div className="flex space-x-4">
                        {['ALL TASKS', 'DISCOVERY CALLS', 'CHECK IN'].map((filter) => (
                            <button
                                key={filter}
                                onClick={() => setSelectedFilter(filter)}
                                className={`px-4 py-2 rounded-lg font-unbounded-500 ${
                                    selectedFilter === filter
                                        ? 'bg-[#222527] text-white'
                                        : 'text-gray-500 hover:bg-[#222527]'
                                }`}
                            >
                                {filter}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Schedule List */}
                <div className="space-y-4">
                    {schedules.map((schedule) => (
                        <div key={schedule.id} className="flex items-center bg-[#111315] p-4 rounded-xl">
                            <input
                                type="checkbox"
                                className="mr-4 h-5 w-5 rounded border-gray-600 bg-transparent"
                            />
                            <div className="flex-1">
                                <h4 className="font-unbounded-700">{schedule.name}</h4>
                                <div className="flex items-center space-x-2 mt-1">
                                    <span className="text-sm text-gray-500">
                                        {schedule.time}
                                    </span>
                                    <span className="text-sm text-gray-500">•</span>
                                    <span className="text-sm text-gray-500">
                                        {schedule.date}
                                    </span>
                                </div>
                            </div>
                            <div className={`px-4 py-2 rounded-lg text-sm font-unbounded-500 ${getTaskTypeStyle(schedule.type)}`}>
                                {schedule.type.toUpperCase()}
                            </div>
                            <button className="ml-4 text-gray-500 hover:text-white">
                                ⋮
                            </button>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
} 