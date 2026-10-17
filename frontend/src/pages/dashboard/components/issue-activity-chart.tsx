import { Bar, BarChart, ResponsiveContainer, XAxis, YAxis, Tooltip } from 'recharts'

interface IssueActivityChartProps {
  data: Array<{ month: string; completed: number; created: number }>
}

export function IssueActivityChart({ data }: IssueActivityChartProps) {
  return (
    <ResponsiveContainer width="100%" height={350}>
      <BarChart data={data}>
        <XAxis dataKey="month" stroke="#888888" fontSize={12} tickLine={false} axisLine={false} />
        <YAxis
          stroke="#888888"
          fontSize={12}
          tickLine={false}
          axisLine={false}
          allowDecimals={false}
        />
        <Tooltip
          contentStyle={{ borderRadius: 8, fontSize: 12 }}
          labelStyle={{ fontWeight: 600 }}
        />
        <Bar dataKey="created" name="Creadas" fill="#6366f1" radius={[4, 4, 0, 0]} opacity={0.4} />
        <Bar dataKey="completed" name="Completadas" fill="#10b981" radius={[4, 4, 0, 0]} />
      </BarChart>
    </ResponsiveContainer>
  )
}
